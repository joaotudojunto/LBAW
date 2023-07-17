--.mode columns
--.headers on
--Pragma Foreign_Keys = on;

DROP SCHEMA IF EXISTS lbaw22104 CASCADE;
CREATE SCHEMA lbaw22104;
SET SEARCH_PATH TO lbaw22104;

-- Drop tables
DROP TABLE IF EXISTS member CASCADE;
DROP TABLE IF EXISTS member_follow CASCADE;
DROP TABLE IF EXISTS administrator CASCADE;
DROP TABLE IF EXISTS news_post CASCADE;
DROP TABLE IF EXISTS tag CASCADE;
DROP TABLE IF EXISTS tag_follow CASCADE;
DROP TABLE IF EXISTS comment CASCADE;
DROP TABLE IF EXISTS vote CASCADE;
DROP TABLE IF EXISTS warning CASCADE;
DROP TABLE IF EXISTS post_image CASCADE;
DROP TABLE IF EXISTS notifications CASCADE;
DROP TABLE IF EXISTS report CASCADE;
DROP TABLE IF EXISTS post_tag CASCADE;
DROP TABLE IF EXISTS post_report CASCADE;
DROP TABLE IF EXISTS comment_report CASCADE;
DROP TABLE IF EXISTS tag_report CASCADE;
DROP TABLE IF EXISTS member_report CASCADE;


-- Create tables --

CREATE TABLE member (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    contact TEXT NOT NULL,
    password TEXT NOT NULL,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    isBanned BOOLEAN NOT NULL DEFAULT false,
    admin BOOLEAN NOT NULL DEFAULT false,
    ts_vectors TSVECTOR,
    remember_token VARCHAR
);


CREATE TABLE member_follow (
    id_followed SERIAL REFERENCES member(id) ON DELETE CASCADE,
    id_follower SERIAL REFERENCES member(id) ON DELETE CASCADE,
    PRIMARY KEY(id_follower, id_followed),
    CONSTRAINT follow_ids CHECK (id_followed <> id_follower)
);

CREATE TABLE administrator (
    id SERIAL PRIMARY KEY REFERENCES member(id) ON DELETE CASCADE
);

CREATE TABLE news_post (
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    score INTEGER DEFAULT 0 NOT NULL,
    date_time timestamp NOT NULL DEFAULT now()::timestamp(0),
    id_owner INT REFERENCES member(id) ON DELETE CASCADE
                                                  ON UPDATE CASCADE,
    ts_vectors TSVECTOR
);

CREATE TABLE tag (
    id SERIAL PRIMARY KEY,
    name TEXT UNIQUE NOT NULL,
    ts_vectors TSVECTOR
);


CREATE TABLE tag_follow (
    id_tag INT REFERENCES tag(id) ON DELETE CASCADE,
    id_member INT REFERENCES member(id) ON DELETE CASCADE,
    PRIMARY KEY(id_tag, id_member)
);

CREATE TABLE post_tag (
    id_post integer REFERENCES news_post(id) ON DELETE CASCADE,
    id_tag integer REFERENCES tag(id) ON DELETE CASCADE,
    PRIMARY KEY(id_post, id_tag)
);

CREATE TABLE comment (
    id SERIAL PRIMARY KEY,
    body TEXT NOT NULL,
    score INTEGER DEFAULT 0 NOT NULL,
    date_time TIMESTAMP NOT NULL DEFAULT now()::timestamp(0),
    id_owner INT NOT NULL REFERENCES member(id) ON DELETE CASCADE,
    id_post INT NOT NULL REFERENCES news_post(id) ON DELETE CASCADE
);



CREATE TABLE vote (
    id SERIAL PRIMARY KEY,
    id_voter INT REFERENCES member(id) ON DELETE CASCADE,
    upvote BOOLEAN NOT NULL,
    vote_type TEXT NOT NULL,
    id_post INT REFERENCES news_post(id) ON DELETE CASCADE,
    id_comment INT REFERENCES comment(id) ON DELETE CASCADE,
    CONSTRAINT vote_news_post CHECK ((vote_type = 'news_post' AND id_post IS NOT NULL AND id_comment IS NULL) OR
                                (vote_type = 'comment' AND id_post IS NULL AND id_comment IS NOT NULL))
);

CREATE TABLE warning (
    id SERIAL PRIMARY KEY,
    body TEXT NOT NULL,
    seen BOOLEAN NOT NULL,
    AdminID INT REFERENCES administrator(id) ON DELETE CASCADE
                                            ON UPDATE CASCADE,
    userID INT REFERENCES member(id) ON DELETE CASCADE
                                            ON UPDATE CASCADE
);

CREATE TABLE post_image (
    id serial PRIMARY KEY,
    id_post INT NOT NULL REFERENCES news_post(id) ON DELETE CASCADE,
    file_path TEXT NOT NULL
);

CREATE TABLE notifications(
    id uuid PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id INTEGER NOT NULL,
    data TEXT NOT NULL,
    read_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE post_report (
    id serial PRIMARY KEY,
    id_reporter integer REFERENCES member(id) ON DELETE CASCADE,
    id_post integer REFERENCES news_post(id) ON DELETE CASCADE,
    body text NOT NULL,
    date_time timestamp NOT NULL DEFAULT now()::timestamp(0)
);

CREATE TABLE comment_report (
    id serial PRIMARY KEY,
    id_reporter integer REFERENCES member(id) ON DELETE CASCADE,
    id_comment integer REFERENCES comment(id) ON DELETE CASCADE,
    body text NOT NULL,
    date_time timestamp NOT NULL DEFAULT now()::timestamp(0)
);

CREATE TABLE tag_report (
    id serial PRIMARY KEY,
    id_reporter integer REFERENCES member(id) ON DELETE CASCADE,
    id_tag integer REFERENCES tag(id) ON DELETE CASCADE,
    body text NOT NULL,
    date_time timestamp NOT NULL DEFAULT now()::timestamp(0)
);

CREATE TABLE member_report (
    id serial PRIMARY KEY,
    id_reporter integer REFERENCES member(id) ON DELETE CASCADE,
    id_reported integer REFERENCES member(id) ON DELETE CASCADE,
    body text NOT NULL,
    date_time timestamp NOT NULL DEFAULT now()::timestamp(0),
    CONSTRAINT member_report_ids CHECK (id_reporter <> id_reported)
);

CREATE TABLE password_resets (
    email      VARCHAR NOT NULL,
    token      VARCHAR NOT NULL,
    created_at timestamp NOT NULL DEFAULT now()::timestamp(0)
);


-----------------------------------------
-- INDEXES
-----------------------------------------

DROP INDEX IF EXISTS password_resets_email_index;
CREATE INDEX password_resets_email_index ON password_resets (email);

DROP INDEX IF EXISTS password_resets_token_index;
create index password_resets_token_index ON password_resets (token);

DROP INDEX IF EXISTS news_post_search_datetime;
CREATE INDEX news_post_search_datetime ON news_post USING btree (date_time);

DROP INDEX IF EXISTS comments_search_datetime;
CREATE INDEX comments_search_datetime ON news_post USING btree (date_time);

DROP INDEX IF EXISTS search_post_user;
CREATE INDEX search_post_user ON news_post USING hash (id_owner);

DROP INDEX IF EXISTS search_comment_owner;
CREATE INDEX search_comment_owner ON comment USING hash (id_owner);

DROP INDEX IF EXISTS user_id_follower;
CREATE INDEX user_id_follower ON member_follow USING hash (id_follower);

-- FTS INDEXES

DROP INDEX IF EXISTS search_news_post;
CREATE INDEX fts_news_post ON news_post USING gist (ts_vectors);

DROP INDEX IF EXISTS search_user;
CREATE INDEX IF NOT EXISTS user_username ON member USING hash (username);

-- INDEXES WITH CONSTRAINT-ENFORCING

DROP INDEX IF EXISTS unique_lowercase_username;
CREATE INDEX unique_lowercase_username ON member (lower(username));

DROP INDEX IF EXISTS unique_lowercase_email;
CREATE INDEX unique_lowercase_email ON member (lower(email));

DROP INDEX IF EXISTS unique_lowercase_news_post;
CREATE INDEX unique_lowercase_news_post ON news_post (lower(title));

-----------------------------------------
-- TRIGGERS and UDFs
-----------------------------------------

--TRIGGER01

-- creates the tsvectors for search using title and body of news_post
DROP FUNCTION IF EXISTS tsvectors_news_post_update_trigger() CASCADE;

CREATE FUNCTION tsvectors_news_post_update_trigger() RETURNS TRIGGER AS $tsvectors_news_post_update$
BEGIN
    IF TG_OP = 'INSERT' THEN
        NEW.ts_vectors = (
            setweight(to_tsvector('english', NEW.title), 'A') ||
            setweight(to_tsvector('english', NEW.body), 'B')
        );
    END IF;
    IF TG_OP = 'UPDATE' THEN
        IF (NEW.title <> OLD.title OR NEW.body <> OLD.body) THEN NEW.ts_vectors = (
            setweight(to_tsvector('english', NEW.title), 'A') ||
            setweight(to_tsvector('english', NEW.body), 'B')
        );
        END IF;
    END IF;
    RETURN NEW;
END;
$tsvectors_news_post_update$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tsvectors_news_post_update ON news_post CASCADE;

CREATE TRIGGER tsvectors_news_post_update
BEFORE INSERT OR UPDATE ON news_post
FOR EACH ROW
EXECUTE FUNCTION tsvectors_news_post_update_trigger();

-- creates the tsvectors for search using name of tags
DROP FUNCTION IF EXISTS tsvectors_tag_update_trigger() CASCADE;

CREATE FUNCTION tsvectors_tag_update_trigger() RETURNS TRIGGER AS $tsvectors_tag_update$
BEGIN
    IF TG_OP = 'INSERT' THEN
        NEW.ts_vectors = (
            setweight(to_tsvector('english', NEW.name), 'A')
        );
    END IF;
    IF TG_OP = 'UPDATE' THEN
        IF (NEW.name <> OLD.name ) THEN NEW.ts_vectors = (
            setweight(to_tsvector('english', NEW.name), 'A')
        );
        END IF;
    END IF;
    RETURN NEW;
END;
$tsvectors_tag_update$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tsvectors_tag_update ON tag CASCADE;

CREATE TRIGGER tsvectors_tag_update
BEFORE INSERT OR UPDATE ON tag
FOR EACH ROW
EXECUTE FUNCTION tsvectors_tag_update_trigger();

-- creates the tsvectors for search using name of members
DROP FUNCTION IF EXISTS tsvectors_member_update_trigger() CASCADE;

CREATE FUNCTION tsvectors_member_update_trigger() RETURNS TRIGGER AS $tsvectors_member_update$
BEGIN
    IF TG_OP = 'INSERT' THEN
        NEW.ts_vectors = (
            setweight(to_tsvector('english', NEW.username), 'A')
        );
    END IF;
    IF TG_OP = 'UPDATE' THEN
        IF (NEW.username <> OLD.username ) THEN NEW.ts_vectors = (
            setweight(to_tsvector('english', NEW.username), 'A')
        );
        END IF;
    END IF;
    RETURN NEW;
END;
$tsvectors_member_update$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tsvectors_member_update ON member CASCADE;

CREATE TRIGGER tsvectors_member_update
BEFORE INSERT OR UPDATE ON member
FOR EACH ROW
EXECUTE FUNCTION tsvectors_member_update_trigger();


--TRIGGER02
-- updates the tsvectors on news_post after comment insert or update

DROP FUNCTION IF EXISTS tsvectors_comment_update_trigger() CASCADE;

CREATE FUNCTION tsvectors_comment_update_trigger() RETURNS TRIGGER AS $tsvectors_comment_update$
BEGIN
    IF TG_OP = 'INSERT' THEN
        UPDATE news_post
        SET ts_vectors = (
            ts_vectors ||
            setweight(to_tsvector('english', NEW.body), 'C'))
        WHERE id = NEW.id_post;
    END IF;
    IF TG_OP = 'UPDATE' THEN
        IF (NEW.body <> OLD.body) THEN
            UPDATE news_post
            SET ts_vectors = (
                ts_vectors ||
                setweight(to_tsvector('english', NEW.body), 'C'))
            WHERE id = NEW.id_post;
        END IF;
    END IF;
    RETURN NEW;
END;
$tsvectors_comment_update$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tsvectors_comment_update ON comment CASCADE;

CREATE TRIGGER tsvectors_comment_update
BEFORE INSERT OR UPDATE ON comment
FOR EACH ROW
EXECUTE FUNCTION tsvectors_comment_update_trigger();

--TRIGGER03
-- updates the tsvectors on news_post after tag insert or update

DROP FUNCTION IF EXISTS tsvectors_tag_update_trigger() CASCADE;

CREATE FUNCTION tsvectors_tag_update_trigger() RETURNS TRIGGER AS $tsvectors_tag_update$
DECLARE tag_name TEXT;
BEGIN
    IF TG_OP = 'INSERT' THEN
        tag_name := (SELECT name FROM tag WHERE id = NEW.id_tag);
        UPDATE news_post
        SET ts_vectors = (
            ts_vectors ||
            setweight(to_tsvector('english', tag_name), 'D'))
        WHERE id = NEW.id_post;
    END IF;
    IF TG_OP = 'UPDATE' THEN
        IF (NEW.name <> OLD.name) THEN
        tag_name := (SELECT name FROM tag WHERE id = NEW.id_tag);
            UPDATE news_post
            SET ts_vectors = (
                ts_vectors ||
                setweight(to_tsvector('english', tag_name), 'D'))
            WHERE id = NEW.id_post;
        END IF;
    END IF;
    RETURN NEW;
END;
$tsvectors_tag_update$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tsvectors_tag_update ON post_tag CASCADE;

CREATE TRIGGER tsvectors_tag_update
BEFORE INSERT OR UPDATE ON post_tag
FOR EACH ROW
EXECUTE FUNCTION tsvectors_tag_update_trigger();

--TRIGGER04
-- inserts a vote in a NewsPost

DROP FUNCTION IF EXISTS insert_post_vote CASCADE;
CREATE FUNCTION insert_post_vote() RETURNS TRIGGER AS $insert_post_vote$
BEGIN
    IF NEW.upvote AND NEW.vote_type = 'news_post' THEN
        UPDATE news_post
            SET score = score + 1
            WHERE NEW.id_post = news_post.id;
    ELSIF NOT NEW.upvote AND NEW.vote_type = 'news_post' THEN
        UPDATE news_post
            SET score = score - 1
            WHERE NEW.id_post = news_post.id;
    END IF;
    RETURN NEW;
END;
$insert_post_vote$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS insert_post_vote ON vote CASCADE;

CREATE TRIGGER insert_post_vote
BEFORE INSERT ON vote
FOR EACH ROW 
EXECUTE FUNCTION insert_post_vote();

--TRIGGER05
-- update a vote in a NewsPost

DROP FUNCTION IF EXISTS update_post_vote CASCADE;
CREATE FUNCTION update_post_vote() RETURNS TRIGGER AS $update_post_vote$
BEGIN
    IF NEW.upvote AND NOT OLD.upvote AND NEW.vote_type = 'news_post' THEN
        UPDATE news_post
            SET score = score + 2
            WHERE NEW.id_post = news_post.id;
    ELSIF NOT NEW.upvote AND OLD.upvote AND NEW.vote_type = 'news_post' THEN
        UPDATE news_post
            SET score = score - 2
            WHERE NEW.id_post = news_post.id;
    END IF;
    RETURN NEW;
END;
$update_post_vote$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS update_post_vote ON vote CASCADE;

CREATE TRIGGER update_post_vote
BEFORE UPDATE ON vote
FOR EACH ROW 
EXECUTE FUNCTION update_post_vote();

--TRIGGER06
-- delete a vote in a NewsPost

DROP FUNCTION IF EXISTS delete_post_vote CASCADE;
CREATE FUNCTION delete_post_vote() RETURNS TRIGGER AS $delete_post_vote$
BEGIN
    IF OLD.upvote AND OLD.vote_type = 'news_post' THEN
        UPDATE news_post
            SET score = score - 1
            WHERE OLD.id_post = news_post.id;
    ELSIF NOT OLD.upvote AND OLD.vote_type = 'news_post' THEN
        UPDATE news_post
            SET score = score + 1
            WHERE OLD.id_post = news_post.id;
    END IF;
    RETURN OLD;
END;
$delete_post_vote$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS delete_post_vote ON vote CASCADE;

CREATE TRIGGER delete_post_vote
BEFORE DELETE ON vote
FOR EACH ROW 
EXECUTE FUNCTION delete_post_vote();

--TRIGGER07
-- inserts a vote in a Comment

DROP FUNCTION IF EXISTS insert_comment_vote CASCADE;
CREATE FUNCTION insert_comment_vote() RETURNS TRIGGER AS $insert_comment_vote$
BEGIN
    IF NEW.upvote AND NEW.vote_type = 'comment' THEN
        UPDATE comment
            SET score = score + 1
            WHERE NEW.id_comment = comment.id;
    ELSIF NOT NEW.upvote AND NEW.vote_type = 'comment' THEN
        UPDATE comment
            SET score = score - 1
            WHERE NEW.id_comment = comment.id;
    END IF;
    RETURN NEW;
END;
$insert_comment_vote$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS insert_comment_vote ON vote CASCADE;

CREATE TRIGGER insert_comment_vote
BEFORE INSERT ON vote
FOR EACH ROW 
EXECUTE FUNCTION insert_comment_vote();

--TRIGGER08
-- update a vote in a Comment

DROP FUNCTION IF EXISTS update_comment_vote CASCADE;
CREATE FUNCTION update_comment_vote() RETURNS TRIGGER AS $update_comment_vote$
BEGIN
    IF NEW.upvote AND NOT OLD.upvote AND NEW.vote_type = 'comment' THEN
        UPDATE comment
            SET score = score + 2
            WHERE NEW.id_comment = comment.id;
    ELSIF NOT NEW.upvote AND OLD.upvote AND NEW.vote_type = 'comment' THEN
        UPDATE comment
            SET score = score - 2
            WHERE NEW.id_comment = comment.id;
    END IF;
    RETURN NEW;
END;
$update_comment_vote$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS update_comment_vote ON vote CASCADE;

CREATE TRIGGER update_comment_vote
BEFORE UPDATE ON vote
FOR EACH ROW 
EXECUTE FUNCTION update_comment_vote();

--TRIGGER08
-- delete a vote in a Comment

DROP FUNCTION IF EXISTS delete_comment_vote CASCADE;
CREATE FUNCTION delete_comment_vote() RETURNS TRIGGER AS $delete_comment_vote$
BEGIN
    IF OLD.upvote AND OLD.vote_type = 'comment' THEN
        UPDATE comment
            SET score = score - 1
            WHERE OLD.id_comment = comment.id;
    ELSIF NOT OLD.upvote AND OLD.vote_type = 'comment' THEN
        UPDATE comment
            SET score = score + 1
            WHERE OLD.id_comment = comment.id;
    END IF;
    RETURN OLD;
END;
$delete_comment_vote$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS delete_comment_vote ON vote CASCADE;

CREATE TRIGGER delete_comment_vote
BEFORE DELETE ON vote
FOR EACH ROW 
EXECUTE FUNCTION delete_comment_vote();

-----------------------------------------
-- end
-----------------------------------------

insert into member (name, contact, password, username, email, isBanned, admin) values ('LBAW22104', '5339507406', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'FEUP LBAW', 'lbaw22104@gmail.com', false, true);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Fabio', '5339507406', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'fcheesworth0', 'fchaster0@people.com.cn', false, true);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Rose', '9549708101', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'romand1', 'rcarling1@hugedomains.com', false, false);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Laurens', '3563230462', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'lnesey2', 'locarran2@sourceforge.net', false, false);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Hiram', '2574976877', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'hdedenham3', 'hrosendall3@rediff.com', false, true);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Coretta', '8232156699', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'cbever4', 'crugg4@usatoday.com', false, false);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Mira', '8771615743', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'msummerly5', 'mnorwell5@sciencedaily.com', false, true);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Justus', '1178391787', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'jbunning6', 'jtirrey6@istockphoto.com', false, true);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Ladonna', '2755474348', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'lwaistall7', 'lbremen7@baidu.com', false, false);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Georgiana', '7833384994', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'gitscowics8', 'grobjant8@weebly.com', false, true);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Anthea', '5247202365', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'aschubert9', 'aphillcock9@joomla.org', false, true);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Corby', '1736304211', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'cgoodbara', 'clovicka@ox.ac.uk', false, true);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Crysta', '1988580668', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'cmacnucatorb', 'cphillipb@wunderground.com', false, false);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Fitzgerald', '9484329910', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'fkingsmillc', 'fhammersleyc@dyndns.org', false, true);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Letitia', '8578964402', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'lwrayd', 'lzanussiid@bbb.org', false, false);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Garry', '3332380495', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'gbradberrye', 'gmcfatere@addthis.com', false, false);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Leupold', '6318721917', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'lcorryerf', 'ldutchburnf@yandex.ru', false, true);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Edgardo', '8975664973', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'efossettg', 'ecambling@wikimedia.org', false, true);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Dasi', '3499623323', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'dselwayh', 'dfavellh@wikia.com', false, false);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Pearle', '4489865300', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'pbadhami', 'pwymeri@canalblog.com', false, true);
insert into member (name, contact, password, username, email, isBanned, admin) values ('Sergei', '2158682484', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'sardyj', 'sscotsbrookj@blogs.com', false, false);

INSERT INTO member_follow (id_followed, id_follower) VALUES (1,6);
INSERT INTO member_follow (id_followed, id_follower) VALUES (1,10);
INSERT INTO member_follow (id_followed, id_follower) VALUES (1,11);
INSERT INTO member_follow (id_followed, id_follower) VALUES (1,19);
INSERT INTO member_follow (id_followed, id_follower) VALUES (2,8);
INSERT INTO member_follow (id_followed, id_follower) VALUES (3,9);
INSERT INTO member_follow (id_followed, id_follower) VALUES (3,15);
INSERT INTO member_follow (id_followed, id_follower) VALUES (4,1);
INSERT INTO member_follow (id_followed, id_follower) VALUES (4,2);
INSERT INTO member_follow (id_followed, id_follower) VALUES (5,7);
INSERT INTO member_follow (id_followed, id_follower) VALUES (5,14);
INSERT INTO member_follow (id_followed, id_follower) VALUES (5,18);
INSERT INTO member_follow (id_followed, id_follower) VALUES (5,20);
INSERT INTO member_follow (id_followed, id_follower) VALUES (6,3);
INSERT INTO member_follow (id_followed, id_follower) VALUES (7,2);
INSERT INTO member_follow (id_followed, id_follower) VALUES (7,8);
INSERT INTO member_follow (id_followed, id_follower) VALUES (8,4);
INSERT INTO member_follow (id_followed, id_follower) VALUES (9,11);
INSERT INTO member_follow (id_followed, id_follower) VALUES (10,6);
INSERT INTO member_follow (id_followed, id_follower) VALUES (10,17);
INSERT INTO member_follow (id_followed, id_follower) VALUES (10,19);
INSERT INTO member_follow (id_followed, id_follower) VALUES (11,20);
INSERT INTO member_follow (id_followed, id_follower) VALUES (12,16);

insert into administrator (id) values (1);
insert into administrator (id) values (3);
insert into administrator (id) values (7);
insert into administrator (id) values (13);
insert into administrator (id) values (17);

insert into news_post (title, body, date_time, id_owner) values ('Elon Musk buys Twitter!', 'After months of waffling, lawsuits, verbal mudslinging and the near miss of a full blown trial, Elon Musk now owns Twitter.On Thursday night, Mr. Musk closed his $44 billion deal to buy the social media service, said three people with knowledge of the situation. He also began cleaning house, with at least four top Twitter executives — including the chief executive and chief financial officer — getting fired on Thursday. Mr. Musk had arrived at Twitter’s San Francisco headquarters on Wednesday and met with engineers and ad executives.', '2022-12-20 01:03:05', 10);
insert into news_post (title, body, date_time, id_owner) values ('FTX crypto exchange owes biggest creditors $3.1bn', 'FTX and its affiliates filed for bankruptcy in Delaware on Nov. 11 in one of the highest-profile crypto blowups, leaving an estimated 1 million customers and other investors facing total losses in the billions of dollars.', '2022-12-23 02:04:06', 20);
insert into news_post (title, body, date_time, id_owner) values ('World of Warcraft to go offline in China', 'World of Warcraft, Overwatch and Diablo 3 are among the big Activision Blizzard video-games titles that will disappear in China in January 2023.The games developer and NetEase, the company that provides access to the games in China, have failed to renew their 14-year-old licensing agreement.All games require a local publisher and licences from the Chinese government to operate there.Activision said it was looking for alternatives.In the meantime, new sales would halt in the next few days. Acquiring a new publisher and new licences could take a long time', '2022-12-29 03:05:07', 3);
insert into news_post (title, body, date_time, id_owner) values ('What is behind the big tech companies job cuts?', 'The first sign of job cuts at Amazon came from LinkedIn posts from laid-off employees.Then, Amazons devices boss, Dave Limp, announced: "It pains me... We will lose talented Amazonians from the devices & services org".Across the tech industry, at firms like Twitter, Meta, Coinbase and Snap, workers have announced they are "seeking new opportunities".Worldwide, more than 120,000 jobs have been lost, according to the Layoffs.fyi website, which tracks tech job cuts.', '2022-12-28 04:06:08', 4);
insert into news_post (title, body, date_time, id_owner) values ('Tesla safety at centre of South Korean trial over fiery, fatal crash', 'SEOUL, Nov 21 (Reuters) - In an upscale Seoul neighbourhood two years ago, a white Tesla Model X smashed into a parking lot wall. The fiery crash killed a prominent lawyer - a close friend of South Koreas president.Prosecutors have charged the driver with involuntary manslaughter. He blames Tesla.Choi Woan-jong, who had eked out a living by driving drunk people home in their own cars, says the Model X sped out of control on its own and that the brakes failed in the December 2020 accident.', '2022-12-21 05:07:09', 5);
insert into news_post (title, body, date_time, id_owner) values ('Tesla recalls 321,000 U.S. vehicles over rear light issue', 'Tesla has recalled more than 321,000 vehicles in the United States because of a tail light issue, in the latest trouble to hit the electric vehicle giant led by controversial billionaire Elon Musk.', '2023-01-01 06:08:10', 8);
insert into news_post (title, body, date_time, id_owner) values ('Twitter should convey how it is protecting Americans online data: White House official', 'WASHINGTON – A White House official said Friday that “this administration believes every company – including social media companies – should take all necessary steps to protect the safety of Americans online data. “Twitter should speak to how they are ensuring that happens, the official said', '2022-12-31 07:09:11', 7);
insert into news_post (title, body, date_time, id_owner) values ('Week 46 in review: Snapdragon 8 Gen 2 arrives, Realme 10 Pro and Pro+ announced', 'The past seven days were marked by the annual Qualcomm summit in Hawaii, where the company announced its new flagship chipset Snapdragon 8 Gen 2. Pretty much all major manufacturers confirmed they will launch a phone with the SoC but the latest reports revealed Samsung is getting a special version with higher frequency.', '2022-12-27 08:10:12', 11);
insert into news_post (title, body, date_time, id_owner) values ('Flashback: how Amazon Fire Phones big bet on 3D and impulse purchases failed', 'In 2004 Amazon CEO Jeff Bezos created a team to build an ebook reader – in 2007 the Kindle was born and it went on to dominate its market. Then in 2011 the Kindle Fire Android tablet arrived and while it couldnt quite achieve the same domination it became a major player in its niche. What was next?', '2023-01-02 09:11:13', 7);
insert into news_post (title, body, date_time, id_owner) values ('Weekly poll: do flagship chipsets still matter?', 'This week Qualcomm unveiled the Snapdragon 8 Gen 2. The week before MediaTek announced its Dimensity 9200. And, of course, Apple’s iPhone 14 series came out recently, but only the Pros got the new chipset. The next generation flagship chips are ready to prove their mettle in benchmarks – but do flagship chipsets even matter ?', '2022-12-28 10:12:14', 10);
insert into news_post (title, body, date_time, id_owner) values ('Apple is bringing a proper battery-saving mode to watchOS 9', 'In a new watchOS 9 update for Apple Watch, the company is finally bringing a real power-saving mode to extend the watchs battery life when needed. It can be activated manually through the Control center or the settings menu. It also prompts the user when theres a 10% battery charge left. It turns off automatically once it reaches 80% on the charger.', '2022-12-28 11:13:15', 13);
insert into news_post (title, body, date_time, id_owner) values ('Cybersecurity: Microsoft got hacked', 'Microsoft this week confirmed that it inadvertently exposed information related to thousands of customers following a security lapse that left an endpoint publicly accessible over the internet sans any authentication.', '2022-12-30 12:14:16', 12);
insert into news_post (title, body, date_time, id_owner) values ('AR-based search with Live View for Google Maps coming soon', 'Google announced an AR-based Live View search for Maps back in September and the company is now ready to move forward with the update. The new functionality, however, will be made available in select cities starting with Los Angeles, London, New York, Paris, San Francisco and Tokyo.', '2022-12-25 13:15:17', 13);
insert into news_post (title, body, date_time, id_owner) values ('OnePlus announces Android 13-based OxygenOS 13 Open Beta Test for Nord CE 2 Lite 5G', 'OnePlus has announced the Android 13-based OxygenOS 13 Open Beta Test (OBT) for the OnePlus Nord CE 2 Lite 5G in India. Those interested can apply by navigating to their OnePlus Nord CE 2 Lite 5Gs Settings > About device menu, tapping Up to date, tapping the icon in the top-right, then going to Beta program > Beta, and submitting the required information. You will receive the update within five workdays if your application is accepted, and you can check for it by heading to your phones Settings > About device > Download Now menu.', '2022-12-29 14:16:18', 14);
insert into news_post (title, body, date_time, id_owner) values ('Samsung Galaxy Z Fold3 and Z Flip3 also get One UI 5.0 udpate', 'Samsung is red hot with its One UI 5.0 rollout and the latest devices to get the new firmware update are the Galaxy Z Fold3 and Z Flip3 – last year’s flagship foldables from Samsung. Users who were part of the One UI 5 beta program are receiving the firmware update version that ends in DVK3 and we can expect it to spread to more users in the coming days.', '2022-12-30 16:18:20', 16);
insert into news_post (title, body, date_time, id_owner) values ('The Prius Gets a Redesign That Actually Looks Cool', 'TOYOTA’S GOT A sleek and shiny new Prius, and the auto press seems to agree: This one looks pretty cool. Previous Prius models have long been seen as, uhh, less than cool, with their awkwardly boxy teardrop shape and normcore vibe. The 2023 Prius, by comparison, looks chic, with a sleek body that squishes down that Prius teardrop into something resembling a Tesla.', '2023-01-01 16:18:20', 16);
insert into news_post (title, body, date_time, id_owner) values ('Oukitel WP21 is a rugged smartphone with Helio G99 SoC and a 9,800 mAh battery with 66W charging', 'The latest phone from Oukitel is here with the WP21 and it brings a mix of the usual rugged phone specs like water and dust resistance and a large 9,800 mAh battery with few other tricks like a second AMOLED screen on the back and the MediaTek Helio G99 chipset.', '2022-12-31 17:19:21', 17);
insert into news_post (title, body, date_time, id_owner) values ('Prototype outward foldable from Xiaomi leaks', 'Back in 2019, Xiaomi filed a patent with the CNIPA for an outward folding phone which resembled Huawei’s Mate X series devices. More than three years later, developer Kuba Wojciechowski (@Za_Raczke) shared some real-life images of the device which actually made it to the prototype stage but sadly never got a commercial release.', '2022-12-26 19:21:23', 19);
insert into news_post (title, body, date_time, id_owner) values ('How to Pair Joy-Cons to Your iPhone and What Games to Play', 'GONE ARE THE days of Flappy Bird. Tap, tap, tapping the pads of your fingers against the smartphone glass may feel insufficient when fighting to win a round of Fortnite. With the introduction of iOS 16, anyone with a compatible iPhone has one more option for gaming controllers: Nintendo’s Joy-Cons.', '2022-12-26 19:21:23', 19);
insert into news_post (title, body, date_time, id_owner) values ('Apple creates new mobile phone', 'Maecenas ut massa quis augue luctus tincidunt. Nulla mollis molestie lorem. Quisque ut erat. Curabitur gravida nisi at nibh. In hac habitasse platea dictumst. Aliquam augue quam, sollicitudin vitae, consectetuer eget, rutrum at, lorem. Integer tincidunt ante vel ipsum. Praesent blandit lacinia erat. Vestibulum sed magna at nunc commodo placerat.', '2022-12-28 16:22:24', 15);
insert into news_post (title, body, date_time, id_owner) values ('Oppo Pad with Dimensity 9000 in the works', 'Oppo is apparently working on a successor to its Oppo Pad with a MediaTek Dimensity 9000 chipset and a 2800x2000px resolution screen. The new rumor comes from Chinese tipster Digital Chat Station and suggests the flagship tablet from Oppo is coming soon.', '2022-12-29 21:23:25', 19);
insert into news_post (title, body, date_time, id_owner) values ('Samsung Galaxy Z Flip4 and Z Fold4 get stable One UI 5.0 and Android 13', 'Last week, Samsung released a stable update to One UI 5.0 based on Android 13 for the Samsung Galaxy Z Flip4 and Z Fold4, but it was made available only to beta testers in the US. Now, the Korean tech giant has open the floodgates and is seeding the new more widely.', '2022-12-30 02:27:29', 6);
insert into news_post (title, body, date_time, id_owner) values ('OnePlus Buds Pro 2 renders emerge', 'OnePlus is readying a new pair of premium wireless earbuds and we finally get our first look thanks to renders shared by the 91mobiles team. OnePlus Buds Pro 2 are shown in their Olive Green color and the buds look nearly identical to their predecessor.', '2022-12-31 22:24:26', 5);
insert into news_post (title, body, date_time, id_owner) values ('Confirmed: the vivo X90 will feature 120W charging, use Samsung E6/BOE Q9 displays', 'Today vivo officially confirmed that the vivo X90 will feature 120W fast charging. The company also revealed some details about the display – users will have a choice between Samsung E6 and BOE Q9 panels (the X80 series used E5 panels). In either case, the display will use 2,160Hz high frequency PWM dimming.', '2022-12-22 23:25:27', 4);
insert into news_post (title, body, date_time, id_owner) values ('Kuo: Only iPhone 15 Pro series will get USB 3.2 speeds', 'Apple is switching to USB-C for its new phones though it remains to be seen when the change is taking place. Noted analyst Ming-Chi Kuo is confident that the port change will happen in the second half of 2023 which should coincide with the iPhone 15 series launch.', '2022-12-28 23:26:28', 20);


insert into tag (name) values ('Consoles');
insert into tag (name) values ('PC Games');
insert into tag (name) values ('Tesla Cars');
insert into tag (name) values ('Social Media');
insert into tag (name) values ('Samsung');
insert into tag (name) values ('Laptops');
insert into tag (name) values ('Sony Audio System');
insert into tag (name) values ('Smart TVs');
insert into tag (name) values ('Android Devices');
insert into tag (name) values ('IOS Devices');
insert into tag (name) values ('Nvidia');

insert into post_tag (id_tag, id_post) values (1,1);
insert into post_tag (id_tag, id_post) values (1,2);
insert into post_tag (id_tag, id_post) values (1,3);
insert into post_tag (id_tag, id_post) values (1,4);
insert into post_tag (id_tag, id_post) values (1,5);
insert into post_tag (id_tag, id_post) values (1,6);
insert into post_tag (id_tag, id_post) values (1,7);
insert into post_tag (id_tag, id_post) values (1,8);
insert into post_tag (id_tag, id_post) values (1,9);
insert into post_tag (id_tag, id_post) values (1,10);
insert into post_tag (id_tag, id_post) values (1,11);
insert into post_tag (id_tag, id_post) values (1,12);
insert into post_tag (id_tag, id_post) values (1,13);
insert into post_tag (id_tag, id_post) values (1,14);
insert into post_tag (id_tag, id_post) values (1,15);
insert into post_tag (id_tag, id_post) values (2,16);
insert into post_tag (id_tag, id_post) values (2,17);
insert into post_tag (id_tag, id_post) values (2,18);
insert into post_tag (id_tag, id_post) values (2,19);
insert into post_tag (id_tag, id_post) values (2,20);
insert into post_tag (id_tag, id_post) values (2,21);
insert into post_tag (id_tag, id_post) values (2,22);
insert into post_tag (id_tag, id_post) values (2,23);
insert into post_tag (id_tag, id_post) values (2,24);
insert into post_tag (id_tag, id_post) values (2,25);
insert into post_tag (id_tag, id_post) values (3,1);
insert into post_tag (id_tag, id_post) values (3,2);
insert into post_tag (id_tag, id_post) values (3,3);
insert into post_tag (id_tag, id_post) values (4,4);
insert into post_tag (id_tag, id_post) values (4,5);
insert into post_tag (id_tag, id_post) values (4,6);
insert into post_tag (id_tag, id_post) values (4,7);
insert into post_tag (id_tag, id_post) values (4,8);
insert into post_tag (id_tag, id_post) values (4,9);
insert into post_tag (id_tag, id_post) values (4,10);
insert into post_tag (id_tag, id_post) values (4,11);
insert into post_tag (id_tag, id_post) values (5,12);
insert into post_tag (id_tag, id_post) values (5,13);
insert into post_tag (id_tag, id_post) values (5,14);
insert into post_tag (id_tag, id_post) values (5,15);
insert into post_tag (id_tag, id_post) values (5,16);
insert into post_tag (id_tag, id_post) values (5,17);
insert into post_tag (id_tag, id_post) values (6,18);
insert into post_tag (id_tag, id_post) values (6,19);
insert into post_tag (id_tag, id_post) values (6,20);
insert into post_tag (id_tag, id_post) values (6,21);
insert into post_tag (id_tag, id_post) values (6,22);
insert into post_tag (id_tag, id_post) values (6,23);
insert into post_tag (id_tag, id_post) values (6,24);
insert into post_tag (id_tag, id_post) values (6,25);
insert into post_tag (id_tag, id_post) values (10,1);
insert into post_tag (id_tag, id_post) values (10,2);
insert into post_tag (id_tag, id_post) values (10,3);
insert into post_tag (id_tag, id_post) values (7,4);
insert into post_tag (id_tag, id_post) values (7,5);
insert into post_tag (id_tag, id_post) values (7,6);
insert into post_tag (id_tag, id_post) values (7,7);
insert into post_tag (id_tag, id_post) values (7,8);
insert into post_tag (id_tag, id_post) values (8,9);
insert into post_tag (id_tag, id_post) values (8,10);
insert into post_tag (id_tag, id_post) values (8,11);
insert into post_tag (id_tag, id_post) values (8,12);
insert into post_tag (id_tag, id_post) values (8,13);
insert into post_tag (id_tag, id_post) values (8,14);
insert into post_tag (id_tag, id_post) values (9,15);
insert into post_tag (id_tag, id_post) values (9,16);
insert into post_tag (id_tag, id_post) values (9,17);
insert into post_tag (id_tag, id_post) values (9,18);
insert into post_tag (id_tag, id_post) values (9,19);
insert into post_tag (id_tag, id_post) values (9,20);
insert into post_tag (id_tag, id_post) values (9,21);
insert into post_tag (id_tag, id_post) values (9,22);
insert into post_tag (id_tag, id_post) values (9,23);
insert into post_tag (id_tag, id_post) values (10,24);
insert into post_tag (id_tag, id_post) values (10,25);


insert into tag_follow (id_tag, id_member) values (1, 3);
insert into tag_follow (id_tag, id_member) values (1, 6);
insert into tag_follow (id_tag, id_member) values (2, 2);
insert into tag_follow (id_tag, id_member) values (2, 12);
insert into tag_follow (id_tag, id_member) values (2, 1);
insert into tag_follow (id_tag, id_member) values (2, 10);
insert into tag_follow (id_tag, id_member) values (3, 20);
insert into tag_follow (id_tag, id_member) values (4, 17);
insert into tag_follow (id_tag, id_member) values (4, 3);
insert into tag_follow (id_tag, id_member) values (5, 4);
insert into tag_follow (id_tag, id_member) values (5, 1);
insert into tag_follow (id_tag, id_member) values (5, 7);
insert into tag_follow (id_tag, id_member) values (5, 2);
insert into tag_follow (id_tag, id_member) values (6, 8);
insert into tag_follow (id_tag, id_member) values (6, 14);
insert into tag_follow (id_tag, id_member) values (7, 12);
insert into tag_follow (id_tag, id_member) values (7, 9);
insert into tag_follow (id_tag, id_member) values (7, 17);
insert into tag_follow (id_tag, id_member) values (7, 16);
insert into tag_follow (id_tag, id_member) values (7, 13);
insert into tag_follow (id_tag, id_member) values (8, 5);
insert into tag_follow (id_tag, id_member) values (8, 15);
insert into tag_follow (id_tag, id_member) values (8, 19);
insert into tag_follow (id_tag, id_member) values (9, 20);
insert into tag_follow (id_tag, id_member) values (9, 8);
insert into tag_follow (id_tag, id_member) values (9, 10);
insert into tag_follow (id_tag, id_member) values (10, 12);
insert into tag_follow (id_tag, id_member) values (10, 14);
insert into tag_follow (id_tag, id_member) values (10, 1);
insert into tag_follow (id_tag, id_member) values (10, 16);
insert into tag_follow (id_tag, id_member) values (11, 9);

insert into comment (body, date_time, id_owner, id_post) values('You are an amazing writer!', '2023-01-02 12:05:33', 3, 1);
insert into comment (body, date_time, id_owner, id_post) values('This is clear, concise, and complete!', '2023-01-02 13:06:34', 4, 2);
insert into comment (body, date_time, id_owner, id_post) values('Keep up the incredible work!', '2023-01-02 14:07:35', 3, 3);
insert into comment (body, date_time, id_owner, id_post) values('This gets my seal of approval!', '2023-01-02 15:08:36', 18, 4);
insert into comment (body, date_time, id_owner, id_post) values('This blew me away!', '2023-01-02 16:09:37', 9, 5);
insert into comment (body, date_time, id_owner, id_post) values('You have brilliant thoughts!', '2023-01-02 17:10:38', 1, 6);
insert into comment (body, date_time, id_owner, id_post) values('You show an impressive grasp on this subject!', '2023-01-02 18:11:39', 5, 7);
insert into comment (body, date_time, id_owner, id_post) values('I discovered something new!', '2023-01-02 19:12:40', 14, 8);
insert into comment (body, date_time, id_owner, id_post) values('You are an amazing writer!', '2023-01-02 20:13:41', 7, 9);
insert into comment (body, date_time, id_owner, id_post) values('This is clear, concise, and complete!', '2023-01-02 21:14:42', 4, 10);
insert into comment (body, date_time, id_owner, id_post) values('I discovered something new!', '2023-01-02 22:15:43', 14, 11);
insert into comment (body, date_time, id_owner, id_post) values('You should be proud!', '2023-01-02 23:16:44', 11, 12);
insert into comment (body, date_time, id_owner, id_post) values('I appreciate your hard work!',  '2023-01-02 21:33:08', 10, 13);
insert into comment (body, date_time, id_owner, id_post) values('You show great attention to detail!', '2023-01-02 06:11:41', 13, 14);
insert into comment (body, date_time, id_owner, id_post) values('Keep up the incredible work!', '2023-01-02 23:01:01', 3, 15);
insert into comment (body, date_time, id_owner, id_post) values('This gets my seal of approval!', '2023-01-02 19:08:22', 18, 16);
insert into comment (body, date_time, id_owner, id_post) values('This blew me away!', '2023-01-02 04:48:01', 9, 17);
insert into comment (body, date_time, id_owner, id_post) values('This is clear, concise, and complete!', '2023-01-02 08:08:17', 4, 18);
insert into comment (body, date_time, id_owner, id_post) values('You are an amazing writer!', '2023-01-02 12:22:15', 7, 19);
insert into comment (body, date_time, id_owner, id_post) values('You have brilliant thoughts!', '2023-01-02 17:02:58', 1,20 );
insert into comment (body, date_time, id_owner, id_post) values('You show an impressive grasp on this subject!', '2023-01-02 05:28:06', 5, 21);
insert into comment (body, date_time, id_owner, id_post) values('I appreciate your hard work!', '2023-01-02 11:10:39', 10, 22);
insert into comment (body, date_time, id_owner, id_post) values('You should be proud!', '2023-01-02 15:47:00', 11, 23);
insert into comment (body, date_time, id_owner, id_post) values('You show great attention to detail!', '2023-01-02 02:01:45', 13, 24);
insert into comment (body, date_time, id_owner, id_post) values('I discovered something new!', '2023-01-02 12:29:34', 14, 25);

insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (1, true, 'comment', NULL, 19);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (2, true, 'comment', NULL, 12);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (3, false, 'comment', NULL, 1);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (4, false, 'comment', NULL, 11);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (5, true, 'comment', NULL, 10);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (6, false, 'comment', NULL, 5);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (7, false, 'comment', NULL, 18);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (8, true, 'comment', NULL, 14);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (9, true, 'comment', NULL, 9);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (10, false, 'comment', NULL, 6);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (11, true, 'comment', NULL, 2);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (12, true, 'comment', NULL, 15);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (13, false, 'comment', NULL, 16);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (14, true, 'comment', NULL, 20);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (15, true, 'comment', NULL, 8);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (16, false, 'comment', NULL, 2);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (17, true, 'comment', NULL, 13);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (18, true, 'comment', NULL, 11);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (19, false, 'comment', NULL, 3);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (20, true, 'comment', NULL, 1);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (1, true, 'news_post', 9, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (2, true, 'news_post', 2, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (3, false, 'news_post', 11, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (4, false, 'news_post', 1,NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (5, true, 'news_post', 20, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (6, false, 'news_post', 16, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (7, true, 'news_post', 8, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (8, false, 'news_post', 4, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (9, true, 'news_post', 19, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (10, false, 'news_post', 16, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (11, true, 'news_post', 12, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (12, false, 'news_post', 5, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (13, true, 'news_post', 6, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (14, false, 'news_post', 10, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (15, true, 'news_post', 18, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (16, false, 'news_post', 12, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (17, true, 'news_post', 3, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (18, false, 'news_post', 1, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (19, true, 'news_post', 13, NULL);
insert into vote (id_voter, upvote, vote_type, id_post, id_comment) values (20, false, 'news_post', 11, NULL);

insert into warning (body, seen, adminID, userID) values ('Varius dui litora platea tempor dapibus nostra aptent ornare diam. Mauris himenaeos vehicula nulla accumsan. Vivamus elementum platea ornare lorem non proin tellus viverra sociosqu. Habitant a senectus eu semper tempor tristique auctor. Curabitur habitasse mollis turpis scelerisque velit. Pharetra curabitur odio dapibus ultricies accumsan et adipiscing mollis.', true, 7, 20);
insert into warning (body, seen, adminID, userID) values ('Elementum nec nibh aenean curabitur velit etiam dui mattis. Duis inceptos ut etiam sed nam. Mi fermentum dictum habitant inceptos.', true, 1, 3);
insert into warning (body, seen, adminID, userID) values ('Vivamus bibendum pellentesque aliquam laoreet viverra. Purus dolor luctus tortor. Ullamcorper vestibulum faucibus augue sapien arcu molestie. Tempus tellus augue habitant eu auctor aliquam nisi amet. Sapien senectus risus mi potenti. Eleifend interdum quisque cursus tincidunt.', false, 13, 1);
insert into warning (body, seen, adminID, userID) values ('Potenti non est ligula tortor pulvinar nostra ante suscipit. Nec turpis eros phasellus vivamus donec. Donec ipsum curabitur vitae. Etiam felis at.', true, 3, 19);
insert into warning (body, seen, adminID, userID) values ('Etiam justo enim curabitur ante sociosqu iaculis ante varius non. Fames porta auctor elementum euismod bibendum metus. Risus viverra neque senectus. Ultrices nostra egestas suspendisse purus placerat. Laoreet arcu mollis consequat laoreet urna mi molestie magna vestibulum.', false, 17, 2);
insert into warning (body, seen, adminID, userID) values ('Ipsum platea cubilia iaculis congue suscipit. Tempus elementum ac in bibendum ullamcorper. Duis ad quis. Odio nam scelerisque condimentum donec velit facilisis quis.', false, 13, 6);
insert into warning (body, seen, adminID, userID) values ('Enim nisi ultrices augue praesent ad nisl iaculis. Lectus curabitur venenatis. Aenean integer fusce.', false, 1, 9);
insert into warning (body, seen, adminID, userID) values ('Sollicitudin netus vestibulum mollis ullamcorper dictum molestie bibendum pretium torquent. Sodales class imperdiet justo non eget curabitur. Pharetra maecenas etiam.', true, 3, 15);
insert into warning (body, seen, adminID, userID) values ('Dictum tincidunt per nunc consequat elit id ut turpis. Quisque ut nunc arcu porttitor arcu cursus eleifend felis luctus. Iaculis risus potenti per amet ultricies elementum ornare. Ornare senectus ultrices.', false, 1, 12);
insert into warning (body, seen, adminID, userID) values ( 'Hendrerit dapibus ultrices leo. Justo fringilla egestas vel per cras. Magna tortor imperdiet.', true, 7, 10);
insert into warning (body, seen, adminID, userID) values ( 'Sollicitudin netus vestibulum mollis ullamcorper dictum molestie bibendum pretium torquent. Sodales class imperdiet justo non eget curabitur. Pharetra maecenas etiam.', true, 1, 6);
insert into warning (body, seen, adminID, userID) values ( 'Hendrerit dapibus ultrices leo. Justo fringilla egestas vel per cras. Magna tortor imperdiet.', false, 3, 1);
insert into warning (body, seen, adminID, userID) values ( 'Enim nisi ultrices augue praesent ad nisl iaculis. Lectus curabitur venenatis. Aenean integer fusce.', true, 7, 5);
insert into warning (body, seen, adminID, userID) values ( 'Elementum nec nibh aenean curabitur velit etiam dui mattis. Duis inceptos ut etiam sed nam. Mi fermentum dictum habitant inceptos.', true, 13, 4);
insert into warning (body, seen, adminID, userID) values ( 'Dictum tincidunt per nunc consequat elit id ut turpis. Quisque ut nunc arcu porttitor arcu cursus eleifend felis luctus. Iaculis risus potenti per amet ultricies elementum ornare. Ornare senectus ultrices.', false, 17, 5);
insert into warning (body, seen, adminID, userID) values ( 'Enim nisi ultrices augue praesent ad nisl iaculis. Lectus curabitur venenatis. Aenean integer fusce.', true, 1, 3);
insert into warning (body, seen, adminID, userID) values ( 'Hendrerit dapibus ultrices leo. Justo fringilla egestas vel per cras. Magna tortor imperdiet.', true, 3, 4);
insert into warning (body, seen, adminID, userID) values ( 'Vivamus bibendum pellentesque aliquam laoreet viverra. Purus dolor luctus tortor. Ullamcorper vestibulum faucibus augue sapien arcu molestie. Tempus tellus augue habitant eu auctor aliquam nisi amet. Sapien senectus risus mi potenti. Eleifend interdum quisque cursus tincidunt.', false, 7, 2);
insert into warning (body, seen, adminID, userID) values ( 'Potenti non est ligula tortor pulvinar nostra ante suscipit. Nec turpis eros phasellus vivamus donec. Donec ipsum curabitur vitae. Etiam felis at.', false, 13, 5);
insert into warning (body, seen, adminID, userID) values ( 'Sollicitudin netus vestibulum mollis ullamcorper dictum molestie bibendum pretium torquent. Sodales class imperdiet justo non eget curabitur. Pharetra maecenas etiam.', true, 17, 3);
insert into warning (body, seen, adminID, userID) values ( 'Hendrerit dapibus ultrices leo. Justo fringilla egestas vel per cras. Magna tortor imperdiet.', false, 1, 4);
insert into warning (body, seen, adminID, userID) values ( 'Elementum nec nibh aenean curabitur velit etiam dui mattis. Duis inceptos ut etiam sed nam. Mi fermentum dictum habitant inceptos.', true, 3, 1);
insert into warning (body, seen, adminID, userID) values ( 'Sollicitudin netus vestibulum mollis ullamcorper dictum molestie bibendum pretium torquent. Sodales class imperdiet justo non eget curabitur. Pharetra maecenas etiam.', false, 7, 2);
insert into warning (body, seen, adminID, userID) values ( 'Etiam justo enim curabitur ante sociosqu iaculis ante varius non. Fames porta auctor elementum euismod bibendum metus. Risus viverra neque senectus. Ultrices nostra egestas suspendisse purus placerat. Laoreet arcu mollis consequat laoreet urna mi molestie magna vestibulum.', false, 13, 7);
insert into warning (body, seen, adminID, userID) values ( 'Ipsum platea cubilia iaculis congue suscipit. Tempus elementum ac in bibendum ullamcorper. Duis ad quis. Odio nam scelerisque condimentum donec velit facilisis quis.', false, 1, 7);
insert into warning (body, seen, adminID, userID) values ( 'Varius dui litora platea tempor dapibus nostra aptent ornare diam. Mauris himenaeos vehicula nulla accumsan. Vivamus elementum platea ornare lorem non proin tellus viverra sociosqu. Habitant a senectus eu semper tempor tristique auctor. Curabitur habitasse mollis turpis scelerisque velit. Pharetra curabitur odio dapibus ultricies accumsan et adipiscing mollis.', false, 17, 7);

insert into post_image (id_post, file_path) values (1, 'post1_image0.jpeg');
insert into post_image (id_post, file_path) values (2, 'post2_image0.jpg');
insert into post_image (id_post, file_path) values (3, 'post3_image0.jpeg');
insert into post_image (id_post, file_path) values (4, 'post4_image0.jpg');
insert into post_image (id_post, file_path) values (5, 'post5_image0.jpg');
insert into post_image (id_post, file_path) values (6, 'post6_image0.jpg');
insert into post_image (id_post, file_path) values (7, 'post7_image0.jpg');
insert into post_image (id_post, file_path) values (8, 'post8_image0.jpeg');
insert into post_image (id_post, file_path) values (9, 'post9_image0.jpg');
insert into post_image (id_post, file_path) values (10, 'post10_image0.jpg');
insert into post_image (id_post, file_path) values (11, 'post11_image0.jpg');
insert into post_image (id_post, file_path) values (12, 'post12_image0.jpg');
insert into post_image (id_post, file_path) values (13, 'post13_image0.jpg');
insert into post_image (id_post, file_path) values (14, 'post14_image0.jpg');
insert into post_image (id_post, file_path) values (15, 'post15_image0.jpg');
insert into post_image (id_post, file_path) values (16, 'post16_image0.jpg');
insert into post_image (id_post, file_path) values (17, 'post17_image0.jpg');
insert into post_image (id_post, file_path) values (18, 'post18_image0.jpg');
insert into post_image (id_post, file_path) values (19, 'post19_image0.jpg');
insert into post_image (id_post, file_path) values (20, 'post20_image0.jpg');
insert into post_image (id_post, file_path) values (21, 'post21_image0.jpg');
insert into post_image (id_post, file_path) values (22, 'post22_image0.jpg');
insert into post_image (id_post, file_path) values (23, 'post23_image0.jpg');
insert into post_image (id_post, file_path) values (24, 'post24_image0.jpg');
insert into post_image (id_post, file_path) values (25, 'post25_image0.jpg');

insert into post_report (id_reporter, id_post, body, date_time) values (19, 9, 'This is misinformation', TIMESTAMP '2023-01-02 09:42:33');
insert into post_report (id_reporter, id_post, body, date_time) values (11, 25, 'This is misinformation', TIMESTAMP '2023-01-02 09:50:35');
insert into post_report (id_reporter, id_post, body, date_time) values (15, 19, 'This is abusive or harassing', TIMESTAMP '2023-01-02 06:39:52');
insert into post_report (id_reporter, id_post, body, date_time) values (10, 3, 'This is spam', TIMESTAMP '2023-01-02 07:45:55');
insert into post_report (id_reporter, id_post, body, date_time) values (6, 2, 'This is abusive or harassing', TIMESTAMP '2023-01-02 22:17:56');
insert into post_report (id_reporter, id_post, body, date_time) values (1, 5, 'This is abusive or harassing', TIMESTAMP '2023-01-02 17:30:00');


insert into comment_report (id_reporter, id_comment, body, date_time) values (1, 25, 'This is abusive or harassing', TIMESTAMP '2023-01-02 22:02:30');
insert into comment_report (id_reporter, id_comment, body, date_time) values (10, 8, 'This is spam', TIMESTAMP '2023-01-02 22:02:40');
insert into comment_report (id_reporter, id_comment, body, date_time) values (15, 6, 'This is abusive or harassing', TIMESTAMP '2023-01-02 13:02:30');
insert into comment_report (id_reporter, id_comment, body, date_time) values (20, 9, 'This is misinformation', TIMESTAMP '2023-01-02 12:52:50');
insert into comment_report (id_reporter, id_comment, body, date_time) values (9, 7, 'This is abusive or harassing', TIMESTAMP '2023-01-02 12:42:30');
insert into comment_report (id_reporter, id_comment, body, date_time) values (4, 4, 'This is misinformation', TIMESTAMP '2023-01-02 06:29:00');


insert into tag_report (id_reporter, id_tag, body, date_time) values (4, 4, 'Posts are offensive', TIMESTAMP '2023-01-02 17:29:45');
insert into tag_report (id_reporter, id_tag, body, date_time) values (5, 2, 'Inapropriate name', TIMESTAMP '2023-01-02 17:30:00');
insert into tag_report (id_reporter, id_tag, body, date_time) values (6, 8, 'This is abusive or harassing', TIMESTAMP '2023-01-02 17:30:00');
insert into tag_report (id_reporter, id_tag, body, date_time) values (3, 1, 'Posts are offensive', TIMESTAMP '2023-01-02 17:30:00');
insert into tag_report (id_reporter, id_tag, body, date_time) values (20, 9, 'This is abusive or harassing', TIMESTAMP '2023-01-02 17:30:00');
insert into tag_report (id_reporter, id_tag, body, date_time) values (17, 8, 'Posts are offensive', TIMESTAMP '2023-01-02 17:30:00');


insert into member_report (id_reporter, id_reported, body, date_time) values (5, 2, 'Impersonating someone', TIMESTAMP '2023-01-02 17:29:33');
insert into member_report (id_reporter, id_reported, body, date_time) values (5, 20, 'Inapropriate username', TIMESTAMP '2023-01-02 17:30:00');
insert into member_report (id_reporter, id_reported, body, date_time) values (20, 6, 'Impersonating someone', TIMESTAMP '2023-01-02 17:30:00');
insert into member_report (id_reporter, id_reported, body, date_time) values (11, 10, 'Posts are offensive', TIMESTAMP '2023-01-02 17:30:00');
insert into member_report (id_reporter, id_reported, body, date_time) values (8, 13, 'Posts are offensive', TIMESTAMP '2023-01-02 17:30:00');
insert into member_report (id_reporter, id_reported, body, date_time) values (10, 15, 'Impersonating someone', TIMESTAMP '2023-01-02 17:30:00');
