<?php
class Playlist {
  function __construct($db) {
    $this->db = $db;
  }

  public function set_item_report($id, $reason) {
    $safe_reason = $this->db->safe($reason);
    $this->db->query("update weekendv2_list SET skip_reason='{$safe_reason}' where id='{$id}' LIMIT 1");
  }

  public function add_copy($id) {
    $id = $this->db->safe($id);
    $query = "
      INSERT INTO weekendv2_list (room_id, song_id, user_id, copy)
      SELECT room_id, song_id, user_id, '1' AS copy FROM weekendv2_list WHERE id=$id limit 1
    ";
    $this->db->query($query);
    return $this->db->last_id();
  }

  public function vote_item($song_id, $vote, $user_email) {
    $song_id = $this->db->safe($song_id);
    $user_email = $this->db->safe($user_email);
    $user_id = $this->db->get_user_id_by_email($user_email);
    $vote = $this->db->safe($vote) === "1" ? 1 : -1 ;
    $sql = "INSERT INTO weekendv2_votes (song_id,user_id, value) VALUES ($song_id, $user_id, $vote) ON DUPLICATE KEY UPDATE value = $vote";
    $this->db->query($sql);
  }

  public function remove_item($song_id) {
    $song_id = $this->db->safe($song_id);
    $this->db->query("UPDATE weekendv2_list SET skip_reason='deleted' WHERE id='{$song_id}' LIMIT 1");
  }

  public function add_item($room_id, $v, $title, $length, $added_by_email) {
    $safe_title = $this->db->safe($title);

    $user_email = $this->db->safe($added_by_email);
    $user_id = $this->db->get_user_id_by_email($user_email);

    //Search if the song exists in DB
    $query = "SELECT id FROM weekendv2_songs WHERE video_id='$v' LIMIT 1";
    $result = $this->db->query($query);
    if ($result){
      $row = $this->db->fetch($result);
      $id = $row['id'];
    } else {
      //Insert new song
      $query = "INSERT INTO weekendv2_songs SET video_id='$v', title='$safe_title', length=$length";
      $this->db->query($query);
      $id = $this->db->insert_id;
    }

    //Add to room list
    $query = "INSERT INTO weekendv2_list SET room_id='$room_id', song_id='$id', user_id=$user_id";
    $this->db->query($query);
  }

  public function is_already_last_in_playlist($room_id, $v) {
    $safe_v = $this->db->safe($v);
    $query = "
      SELECT video_id
      FROM weekendv2_list
      JOIN weekendv2_songs
      ON song_id = weekendv2_songs.id
      WHERE room_id='$room_id'
      ORDER BY weekendv2_list.id DESC
      LIMIT 1
    ";
    $result = $this->db->query($query);
    if (!$result) {
      return false;
    }
    $row = $this->db->fetch($result);
    return ($row['video_id'] == $safe_v ? true : false);
  }

  public function fetch_youtube_video_and_add($room_id, $v, $user_email) {
    $safe_v = $this->db->safe($v);
    if  (strlen($safe_v) != 11) {
      return false;
    }
    //$response = file_get_contents('http://gdata.youtube.com/feeds/api/videos/'.$safe_v);
    $response = system("curl -H 'Host: gdata.youtube.com' http://74.125.195.118/feeds/api/videos/".$safe_v);
    if ($response) {
      //preg_match("/(<media:title.*>)(\b.*\b)(<\/media:title>)/",$response, $matches);
      preg_match("/(<media:title.*>)(.*)(<\/media:title>)/",$response, $matches);

      $title = $matches[2];
      $title = str_replace("'","",$title);
      preg_match("/(<yt:duration seconds=')(\d+)('\/>)/",$response, $matches);
      $length = $matches[2];

      $this->add_item($room_id, $v, $title, $length, $user_email);

      return true;
    }
    return false;
  }
}
?>