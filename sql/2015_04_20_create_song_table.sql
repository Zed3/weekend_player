DROP TABLE IF EXISTS `weekendv2_songs`;
CREATE TABLE IF NOT EXISTS `weekendv2_songs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `video_id` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `length` int(10) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `video_id` (`video_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

TRUNCATE weekendv2_songs;
INSERT INTO weekendv2_songs (
  SELECT
    weekendv2_playlist.id AS id,
    weekendv2_users.id AS user_id,
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


DROP TABLE IF EXISTS `weekendv2_list`;
CREATE TABLE IF NOT EXISTS `weekendv2_list` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `song_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skip_reason` varchar(250) COLLATE utf8_unicode_ci NULL,
  `copy` int(1) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

TRUNCATE weekendv2_list;
INSERT INTO weekendv2_list (
SELECT
  weekendv2_playlist.id AS id,
  weekendv2_playlist.room_id AS room_id,
  weekendv2_songs.id AS song_id,
  user_id,
  skip_reason,
  copy,
  weekendv2_playlist.datetime AS timestamp 
FROM weekendv2_playlist
JOIN weekendv2_users ON added_by_email = email
JOIN weekendv2_songs ON v = video_id
)

