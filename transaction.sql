-- transaction to post news, image and tag at the same time

CREATE PROCEDURE insert_news_image_tag(news_title TEXT, news_body TEXT, news_datetime DATETIME, news_owner INT, file_path TEXT, tags TEXT[])
LANGUAGE SQL
AS $$
	
	-- Insert news post into news table
	INSERT INTO news(title, body, owner)
	values(news_title, news_body, news_datetime, news_owner);
	
	-- Insert news image into image table
	SELECT id AS news_id FROM news WHERE title = news_title;
	INSERT INTO news_image(id_news, file_path)
	values(news_id, file_path);

	-- Insert user-given tags into tag
	var text[];
	BEGIN
	FOR var IN SELECT tags
		LOOP
		INSERT INTO tag(name)
		values(var);
		-- Insert tag_id and news_id in tag_news, associating them
		SELECT id AS tag_id FROM tag WHERE name = var;
		INSERT INTO tag_news(id_tag, id_news)
		values(tag_id, news_id);
		END LOOP;
	END$$
$$

-- The information to be passed to the procedure is given by the user
BEGIN TRANSACTION;

SET TRANSACTION ISOLATION LEVEL SERIALIZABLE READ ONLY;

	CALL insert_news_image_tag(news_title, news_body, news_datetime, news_owner, file_path, tags);

END TRANSACTION;