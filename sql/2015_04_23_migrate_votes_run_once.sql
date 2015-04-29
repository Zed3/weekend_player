DROP TABLE IF EXISTS `weekendv2_votes_old`;
CREATE TABLE weekendv2_votes_old LIKE weekendv2_votes; 
INSERT weekendv2_votes_old SELECT * FROM weekendv2_votes;
TRUNCATE weekendv2_votes;

INSERT weekendv2_votes (song_id, user_id, value) 
SELECT weekendv2_songs.id AS song_id, weekendv2_votes_old.user_id AS user_id, value
FROM weekendv2_votes_old 
JOIN weekendv2_playlist ON weekendv2_votes_old.song_id = weekendv2_playlist.id
JOIN weekendv2_songs ON weekendv2_playlist.v = weekendv2_songs.video_id
GROUP BY video_id, weekendv2_votes_old.user_id
