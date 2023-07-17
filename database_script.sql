--.mode columns
--.headers on
--Pragma Foreign_Keys = on;

-- Drop tables

DROP TABLE IF EXISTS report;
DROP TABLE IF EXISTS notification;
DROP TABLE IF EXISTS news_image;
DROP TABLE IF EXISTS warning;
DROP TABLE IF EXISTS vote;
DROP TABLE IF EXISTS comment;
DROP TABLE IF EXISTS tag_follow;
DROP TABLE IF EXISTS tag_news;
DROP TABLE IF EXISTS tag;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS administrator;
DROP TABLE IF EXISTS authenticatedUser_follow;
DROP TABLE IF EXISTS authenticatedUser;

-- Create tables --

CREATE TABLE authenticatedUser (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    contact TEXT NOT NULL,
    password TEXT NOT NULL,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    isBanned BOOLEAN NOT NULL,
    isAdmin BOOLEAN NOT NULL,
    ts_vectors TSVECTOR
);

CREATE TABLE authenticatedUser_follow (
    id_followed SERIAL REFERENCES authenticatedUser(id) ON DELETE CASCADE,
    id_follower SERIAL REFERENCES authenticatedUser(id) ON DELETE CASCADE,
    PRIMARY KEY(id_follower, id_followed),
    CONSTRAINT follow_ids CHECK (id_followed <> id_follower)
);

CREATE TABLE administrator (
    id SERIAL PRIMARY KEY REFERENCES authenticatedUser(id) ON DELETE CASCADE
);

CREATE TABLE news (
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    date_time timestamp NOT NULL DEFAULT now(),
    id_owner INT REFERENCES authenticatedUser(id) ON DELETE CASCADE                                                                                
                                                  ON UPDATE CASCADE,
    ts_vectors TSVECTOR
);

CREATE TABLE tag (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL
);

CREATE TABLE tag_news (
    id_tag INT REFERENCES tag(id) ON DELETE CASCADE,
    id_news INT references news(id) ON DELETE CASCADE,
    PRIMARY KEY(id_tag, id_news)
);

CREATE TABLE tag_follow (
    id_tag INT REFERENCES tag(id) ON DELETE CASCADE,
    id_authenticatedUser INT REFERENCES authenticatedUser(id) ON DELETE CASCADE,
    PRIMARY KEY(id_tag, id_authenticatedUser)
);

CREATE TABLE comment (
    id SERIAL PRIMARY KEY,
    body TEXT NOT NULL, 
    date_time TIMESTAMP NOT NULL DEFAULT now(),
    id_owner INT NOT NULL REFERENCES authenticatedUser(id) ON DELETE CASCADE,
    id_news INT NOT NULL REFERENCES news(id) ON DELETE CASCADE
);


CREATE TABLE vote (
    id SERIAL PRIMARY KEY,
    id_voter INT REFERENCES authenticatedUser(id) ON DELETE CASCADE,
    upvote BOOLEAN NOT NULL,
    vote_type TEXT NOT NULL,
    id_news INT REFERENCES news(id) ON DELETE CASCADE,
    id_comment INT REFERENCES comment(id) ON DELETE CASCADE,
    CONSTRAINT vote_news CHECK ((vote_type = 'news' AND id_news IS NOT NULL AND id_comment IS NULL) OR
                                (vote_type = 'comment' AND id_news IS NULL AND id_comment IS NOT NULL))
);

CREATE TABLE warning (
    id SERIAL PRIMARY KEY,
    body TEXT NOT NULL,
    seen BOOLEAN NOT NULL, 
    AdminID INT REFERENCES administrator(id) ON DELETE CASCADE                                                                                
                                            ON UPDATE CASCADE,
    authenticatedUserID INT REFERENCES authenticatedUser(id) ON DELETE CASCADE                                                                                
                                            ON UPDATE CASCADE
);

CREATE TABLE news_image (
    id INT PRIMARY KEY,
    id_news INT NOT NULL REFERENCES news(id) ON DELETE CASCADE,
    file_path TEXT NOT NULL
);

CREATE TABLE notification (
    id SERIAL PRIMARY KEY,
    id_notified INT REFERENCES authenticatedUser(id) ON DELETE CASCADE,
    seen BOOLEAN NOT NULL,
    date_time TIMESTAMP NOT NULL DEFAULT now(),
    notification_type TEXT NOT NULL,
    id_warnings INT REFERENCES warning(id) ON DELETE CASCADE,
    id_follower INT REFERENCES authenticatedUser(id) ON DELETE CASCADE,
    id_vote INT REFERENCES vote(id) ON DELETE CASCADE,
    id_comment INT REFERENCES comment(id) ON DELETE CASCADE,
    id_news INT REFERENCES news(id) ON DELETE CASCADE,
    CONSTRAINT notification_check CHECK ((notification_type = 'warning' AND id_warnings IS NOT NULL AND id_follower IS NULL and id_vote IS NULL AND id_comment IS NULL AND id_news IS NULL) OR
                                            (notification_type = 'follow' AND id_warnings IS NULL AND id_follower IS NOT NULL AND id_vote IS NULL AND id_comment IS NULL AND id_news IS NULL) OR
                                            (notification_type = 'vote' AND id_warnings IS NULL AND id_follower IS NULL AND id_vote IS NOT NULL AND id_comment IS NULL AND id_news IS NULL) OR
                                            (notification_type = 'comment' AND id_warnings IS NULL AND id_follower IS NULL AND id_vote IS NULL AND id_comment IS NOT NULL AND id_news IS NULL) OR
                                            (notification_type = 'news' AND id_warnings IS NULL AND id_follower IS NULL AND id_vote IS NULL AND id_comment IS NULL AND id_news IS NOT NULL))
);

CREATE TABLE report (
    id SERIAL PRIMARY KEY,
    id_reporter INT REFERENCES authenticatedUser(id) ON DELETE CASCADE,
    motive TEXT NOT NULL,
    date_time TIMESTAMP NOT NULL DEFAULT now(),
    report_type TEXT NOT NULL,
    id_news INT REFERENCES news(id) ON DELETE CASCADE,
    id_comment INT REFERENCES comment(id) ON DELETE CASCADE,
    CONSTRAINT report_news CHECK ((report_type = 'news' AND id_news IS NOT NULL AND id_comment IS NULL) OR
                                (report_type = 'comment' AND id_news IS NULL AND id_comment IS NOT NULL)) 
);

-----------------------------------------
-- INDEXES
-----------------------------------------

DROP INDEX IF EXISTS news_search_datetime;
CREATE INDEX news_search_datetime ON news USING btree (date_time);

DROP INDEX IF EXISTS comments_search_datetime;
CREATE INDEX comments_search_datetime ON news USING btree (date_time);

DROP INDEX IF EXISTS search_post_user;
CREATE INDEX search_post_user ON news USING hash (id_owner);

DROP INDEX IF EXISTS search_comment_owner;
CREATE INDEX search_comment_owner ON comment USING hash (id_owner);

DROP INDEX IF EXISTS user_id_follower;
CREATE INDEX user_id_follower ON authenticatedUser_follow USING hash (id_follower);

-- FTS INDEXES

DROP INDEX IF EXISTS search_news;
CREATE INDEX fts_news ON news USING gist (ts_vectors);

DROP INDEX IF EXISTS search_user;
CREATE INDEX IF NOT EXISTS authenticatedUser_username ON authenticatedUser USING hash (username);

-- INDEXES WITH CONSTRAINT-ENFORCING

DROP INDEX IF EXISTS unique_lowercase_username;
CREATE INDEX unique_lowercase_username ON authenticatedUser (lower(username));

DROP INDEX IF EXISTS unique_lowercase_email;
CREATE INDEX unique_lowercase_email ON authenticatedUser (lower(email));

DROP INDEX IF EXISTS unique_lowercase_news;
CREATE INDEX unique_lowercase_news ON news (lower(title));


-----------------------------------------
-- TRIGGERS and UDFs
-----------------------------------------

--TRIGGER01

-- creates the tsvectors for search using title and body of news

CREATE FUNCTION tsvectors_news_update() RETURN TRIGGER AS $tsvectors_news_update$
BEGIN
    IF TG_OP = 'INSERT' THEN
        NEW.ts_vectors = (
            setweight(to_tsvector('english', NEW.title), 'A') ||
            setweight(to_tsvector('english', NEW.body), 'B')
        );
    END IF;
    IF TG_OP = 'UPDATE' THEN
        IF (NEW.title <> OLD.title OR NEW.body <> OLD.body) THEN NEW.tsvectors = (
            setweight(to_tsvector('english', NEW.title), 'A') ||
            setweight(to_tsvector('english', NEW.body), 'B')
        );
        END IF;
    END IF;
    RETURN NEW;
END;
$tsvectors_news_update$
LANGUAGE plpgsql;

CREATE TRIGGER tsvectors_news_update
BEFORE INSERT OR UPDATE ON news
FOR EACH ROW
EXECUTE FUNCTION tsvectors_news_update();

--TRIGGER02
-- updates the tsvectors on news after comment insert or update

CREATE FUNCTION tsvectors_comment_update() RETURN TRIGGER AS $tsvectors_comment_update$
BEGIN
    IF TG_OP = 'INSERT' THEN
        UPDATE news
        SET ts_vectors = ( 
            ts_vectors ||
            setweight(to_tsvector('english', NEW.body), 'C'))
        WHERE id = NEW.id_news;
    END IF;
    IF TG_OP = 'UPDATE' THEN
        IF (NEW.body <> OLD.body) THEN 
            UPDATE news 
            SET ts_vectors = (
                ts_vectors ||
                setweight(to_tsvector('english', NEW.body), 'C'))
            WHERE id = NEW.id_news;
        END IF;
    END IF;
    RETURN NEW;
END;
$tsvectors_comment_update$
LANGUAGE plpgsql;


CREATE TRIGGER tsvectors_comment_update
BEFORE INSERT OR UPDATE ON comment
FOR EACH ROW
EXECUTE FUNCTION tsvectors_comment_update();

--TRIGGER03
-- updates the tsvectors on news after tag insert or update

CREATE FUNCTION tsvectors_tag_update() RETURN TRIGGER AS $tsvectors_tag_update$
DECLARE tag_name TEXT;
BEGIN
    IF TG_OP = 'INSERT' THEN
        tag_name := (SELECT name FROM tag WHERE id = NEW.id_tag);
        UPDATE news
        SET ts_vectors = (
            ts_vectors ||
            setweight(to_tsvector('english', tag_name), 'D'))
        WHERE id = NEW.id_news;
    END IF;
    IF TG_OP = 'UPDATE' THEN
        IF (NEW.name <> OLD.name) THEN
        tag_name := (SELECT name FROM tag WHERE id = NEW.id_tag);
            UPDATE news
            SET ts_vectors = (
                ts_vectors ||
                setweight(to_tsvector('english', tag_name), 'D'))
            WHERE id = NEW.id_news;
        END IF;
    END IF;
    RETURN NEW;
END; 
$tsvectors_tag_update$
LANGUAGE plpgsql;

CREATE TRIGGER tsvectors_tag_update
BEFORE INSERT OR UPDATE ON tag_news
FOR EACH ROW
EXECUTE FUNCTION tsvectors_tag_update();

-----------------------------------------
-- end
-----------------------------------------
