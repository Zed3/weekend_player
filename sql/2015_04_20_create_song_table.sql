TRUNCATE weekendv2_songs;
INSERT INTO weekendv2_songs (
  SELECT
    weekendv2_playlist.id AS id,
    weekendv2_users.id AS user_id,
    weekendv2_playlist.room_id AS room_id,
    v AS video_id,
    title,
    length,
    datetime AS timestamp

  FROM weekendv2_playlist
  JOIN weekendv2_users ON added_by_email = email
  WHERE
    copy = 0
  GROUP BY v
  ORDER BY datetime DESC
)

  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `room_id` int(10) NOT NULL,
  `video_id` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `length` int(10) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,