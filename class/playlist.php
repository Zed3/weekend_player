<?
class Playlist {
  function __construct($db) {
    $this->db = $db;
  }

  public function set_item_report($id, $reason) {
    $safe_reason = $this->db->safe($reason);
    $this->db->query("update weekendv2_playlist SET skip_reason='{$safe_reason}' where id='{$id}' LIMIT 1");
  }

  public function add_item($room_id, $v, $title, $length, $added_by_email) {
    $safe_title = $this->db->safe($title);
    $this->db->query("insert into weekendv2_playlist (room_id, v, title, length, added_by_email) values ('{$room_id}', '{$v}', '{$safe_title}', '{$length}', '{$added_by_email}')");
  }

  public function is_already_last_in_playlist($room_id, $v) {
    $safe_v = $this->db->safe($v);
    $result = $this->db->query("select v from weekendv2_playlist where room_id='$room_id' order by id desc limit 1");
    if (!$result) {
      return false;
    }
    $row = $this->db->fetch($result);
    return ($row['v'] == $safe_v ? true : false);
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