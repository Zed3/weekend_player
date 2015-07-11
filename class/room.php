<?php
class Room {
  private $id;
  private $admin;
  private $owner_email;
  private $name;
  private $currently_playing_id;
  private $update_version;
  public $user_permission_array = array(
    "can_add_song" => "User can add songs",
    "can_change_song" => "User can change songs",
    "can_change_permissions" => "User can change User Permissions"
    );

  function __construct($db, $id) {
    $this->db = $db;
    $result = $this->db->query("select * from weekendv2_rooms where id='$id'");
    if (!$result) {
      return false;
    }
    if ($row = $this->db->fetch($result)) {
      $this->id = $row["id"];
      $this->owner_email = $row["owner_email"];
      $this->name = $row["name"];
      $this->currently_playing_id = $row["currently_playing_id"];
      $this->update_version = $row["update_version"];
      $this->options = json_decode($row["room_options"], true);
      $this->user_options = json_decode($row["user_options"], true);
      $this->admin = $this->options['room_admin'];
    }
  }

  public function is_user_allowed_to($user_id, $action) {
    if ($this->is_owner()) { return true; }
    $user_options = $this->get_user_options();
    @$user_options = $user_options[$user_id];
    return @$user_options[$action] == true ? true : false;
  }

  public function update_option($key, $val) {
    $options = $this->options;
    $options[$key] = $val;
    $this->options = $options;
    $this->update_options();
  }

  public function update_user_option($key, $user_id, $val) {
    Log::debug("Setting $key = $val for user $user_id");
    $user_options = $this->user_options;
    $user_options[$user_id][$key] = $val;
    $this->user_options = $user_options;
    $this->update_user_options();
  }

  private function update_options() {
    $options = json_encode($this->options);
    $query = "UPDATE weekendv2_rooms SET room_options='$options' WHERE id={$this->id} LIMIT 1";
    $this->db->query($query);
  }

  private function update_user_options() {
    $options = json_encode($this->user_options);
    $query = "UPDATE weekendv2_rooms SET user_options='$options' WHERE id={$this->id} LIMIT 1";
    $this->db->query($query);
  }

  public function get_id() {
    return $this->id;
  }

  public function get_owner_email() {
    return $this->owner_email;
  }

  public function get_options() {
    return $this->options;
  }

  public function get_user_options() {
    return $this->user_options;
  }

  public function get_owner_name() {
    $result = $this->db->query("select name from weekendv2_users where email='{$this->owner_email}'");
    if (!$result) {
      return "";
    }
    if ($row = $this->db->fetch($result)) {
      return $row["name"];
    }
    return "";
  }

  public function get_name() {
    return $this->name;
  }

  public function get_currently_playing_id() {
    return $this->currently_playing_id;
  }

  public function get_update_version() {
    return $this->update_version;
  }

  public function get_admin_volume() {
    $result = $this->db->query("select admin_volume from weekendv2_rooms where id='{$this->get_id()}' limit 1");
    if (!$result) {
      return 0;
    }
    $row = $this->db->fetch($result);
    return $row["admin_volume"];
  }

  public function set_admin($user_id) {
    $this->update_option('room_admin', $user_id);
    $this->admin = $user_id;
  }

  public function set_admin_volume($volume) {
    $this->db->query("update weekendv2_rooms set admin_volume='{$volume}' where id='{$this->get_id()}' limit 1");
  }

  public function get_admin_random_radio() {
    $result = $this->db->query("select admin_random_radio from weekendv2_rooms where id='{$this->get_id()}' limit 1");
    if (!$result) {
      return 0;
    }
    $row = $this->db->fetch($result);
    return $row["admin_random_radio"];
  }

  public function set_admin_random_radio($bool) {
    $bool = strval($bool);
    if ($bool == "1" || $bool == "0") {
      $this->db->query("update weekendv2_rooms set admin_random_radio='{$bool}' where id='{$this->get_id()}' limit 1");
    }
  }

  public function get_playlist() {
    $query = "
      SELECT title, length, video_id AS v, weekendv2_list.id AS id, weekendv2_users.name AS user_name, song_id, copy
      FROM weekendv2_list
      JOIN weekendv2_users
      ON weekendv2_list.user_id = weekendv2_users.id
      JOIN weekendv2_songs
      ON weekendv2_list.song_id = weekendv2_songs.id
      WHERE skip_reason IS NULL
      AND weekendv2_list.room_id='{$this->get_id()}'
      AND weekendv2_list.id>='{$this->get_currently_playing_id()}'
      ORDER BY weekendv2_list.id DESC
    ";
    $result = $this->db->query($query);
    if (!$result) {
      return array();
    }
    $list = array();
    while ($row = $this->db->fetch($result)) {
      $song_id = $row["song_id"];
      //get votes for each item
      $vote = $this->db->fetch($this->db->query("SELECT IFNULL(SUM(value), 0) AS total FROM weekendv2_votes WHERE song_id = $song_id"));
      $row['votes'] = $vote['total'];

      //get plays for each item
      $vote = $this->db->fetch($this->db->query("SELECT COUNT(*) AS total_played FROM weekendv2_list WHERE song_id = $song_id"));
      $row['total_played'] = $vote['total_played'];

      $list[] = $row;
    }

    return $list;
  }

  public function get_own_playlist() {
    global $Users;
    $user_id = $Users->get_auth_id();
    $query = "
      SELECT
          song_id, title, length, weekendv2_songs.timestamp, video_id
      FROM
          weekendv2_votes
              JOIN
          weekendv2_songs ON weekendv2_songs.id = weekendv2_votes.song_id
      WHERE
          weekendv2_songs.user_id = $user_id
              AND value > 0
      UNION SELECT
          id, title, length, timestamp, video_id
      FROM
          weekendv2_songs
      WHERE
          user_id = $user_id
    ";
    $result = $this->db->query($query);
    if (!$result) {
      return array();
    }
    $list = array();
    while ($row = $this->db->fetch($result)) {
      $song_id = $row["song_id"];
      //get votes for each item
      $vote = $this->db->fetch($this->db->query("SELECT IFNULL(SUM(value), 0) AS total FROM weekendv2_votes WHERE song_id = $song_id"));
      $row['votes'] = $vote['total'];

      //get plays for each item
      $vote = $this->db->fetch($this->db->query("SELECT COUNT(*) AS total_played FROM weekendv2_list WHERE song_id = $song_id"));
      $row['total_played'] = $vote['total_played'];

      $list[] = $row;
    }

    return $list;
  }

  public function get_history() {
    $query = "
      SELECT title, length, video_id AS v, weekendv2_list.id AS id, weekendv2_users.name AS user_name, song_id, copy
      FROM weekendv2_list
      JOIN weekendv2_users
      ON weekendv2_list.user_id = weekendv2_users.id
      JOIN weekendv2_songs
      ON weekendv2_list.song_id = weekendv2_songs.id
      WHERE weekendv2_list.room_id='{$this->get_id()}'
      AND weekendv2_list.id<'{$this->get_currently_playing_id()}'
      ORDER BY weekendv2_list.id DESC LIMIT 50
    ";
    $result = $this->db->query($query);
    if (!$result) {
      return array();
    }
    $list = array();
    while ($row = $this->db->fetch($result)) {
      $song_id = $row["song_id"];
      //get votes for each item
      $vote = $this->db->fetch($this->db->query("SELECT IFNULL(SUM(value), 0) AS total FROM weekendv2_votes WHERE song_id = $song_id"));
      $row['votes'] = $vote['total'];

      //get plays for each item
      $vote = $this->db->fetch($this->db->query("SELECT COUNT(*) AS total_played FROM weekendv2_list WHERE song_id = $song_id"));
      $row['total_played'] = $vote['total_played'];

      $list[] = $row;
    }

//    $list = array_reverse($list);
    return $list;
  }

  public function get_stats() {
    $room_id = $this->get_id();
    $query = "SELECT weekendv2_users.id, name, COUNT(*) AS total_uploaded
              FROM weekendv2_list
              JOIN weekendv2_users ON user_id = weekendv2_users.id
              WHERE copy=0
              GROUP BY email
              ORDER BY total_uploaded DESC
              LIMIT 3";
    $result = $this->db->query($query);
    if (!$result) {
      return array();
    }
    $list = array();
    while ($row = $this->db->fetch($result)) {
      $list[] = $row;
    }
    return $list;
  }

  public function get_chat() {
    $room_id = $this->get_id();
    $query = "SELECT * FROM
          weekendv2_chat
        JOIN weekendv2_users ON weekendv2_chat.user_id = weekendv2_users.id
        WHERE room_id = $room_id
        ORDER BY timestamp
        DESC LIMIT 20";
    $result = $this->db->query($query);
    if (!$result) {
      return array();
    }
    $list = array();
    while ($row = $this->db->fetch($result)) {
      $list[] = $row;
    }
    $list = array_reverse($list);
    return $list;
  }

  public function get_members_count($max_executing_time) {
    $time_margin = 10;
    $total_margin = 0 - $max_executing_time - $time_margin;
    $result = $this->db->query("select count(*) as cc from weekendv2_room_members where weekendv2_room_members.room_id='{$this->get_id()}' and weekendv2_room_members.last_update >= TIMESTAMPADD(SECOND,{$total_margin},NOW())");
    if (!$result) {
      return 0;
    }
    $row = $this->db->fetch($result);
    return $row["cc"];
  }

  public function get_members($max_executing_time) {
    $time_margin = 10;
    $total_margin = 0 - $max_executing_time - $time_margin;
    // get current members:
    //SELECT * FROM weekendv2_room_members LEFT JOIN weekendv2_users ON (member_email = email) WHERE room_id = 2
    $result = $this->db->query("select weekendv2_users.id as user_id, weekendv2_room_members.member_email as member_email, TIMESTAMPDIFF(SECOND, weekendv2_room_members.last_update, NOW()) as last_update, weekendv2_users.name as member_name from weekendv2_room_members left join weekendv2_users on (weekendv2_users.email=weekendv2_room_members.member_email) where weekendv2_room_members.room_id='{$this->get_id()}' and weekendv2_room_members.last_update >= TIMESTAMPADD(SECOND,{$total_margin},NOW())");
    if (!$result) {
      return array();
    }
    $list = array();
    while ($row = $this->db->fetch($result)) {
      $list[] = $row;
    }
    return $list;
  }

  public function flag_active_member($user_email) {
    $this->db->query("INSERT INTO `weekendv2_room_members` (room_id, member_email) VALUES ('{$this->get_id()}','{$user_email}') ON DUPLICATE KEY UPDATE last_update=NOW()");
  }

  public function get_playlist_next_song() {
    $query = "
      SELECT weekendv2_list.id AS id
      FROM weekendv2_list
      JOIN weekendv2_songs
      ON weekendv2_list.song_id = weekendv2_songs.id
      WHERE weekendv2_list.room_id='{$this->get_id()}'
      AND weekendv2_list.id > {$this->get_currently_playing_id()} LIMIT 1
    ";
    $result = $this->db->query($query);
    if (!$result) {
      return false;
    }
    if ($row = $this->db->fetch($result)) {
      return $row["id"];
    }
    return false;
  }

  public function check_if_should_skip() {
    if ($this->get_currently_playing_id() == '0') {
	return true;
    }
    $sql = "SELECT `currently_playing_id` FROM  `weekendv2_rooms` INNER JOIN weekendv2_list ON weekendv2_list.id = weekendv2_rooms.currently_playing_id WHERE `weekendv2_rooms`.id='{$this->get_id()}' AND weekendv2_list.skip_reason IS NOT NULL";
    $result = $this->db->query($sql);
    if (!$result) {
      return false;
    }
    if (!$row = $this->db->fetch($result)) {
      return false;
    }
    return true;
  }

  public function get_random_song() {
    //first, get current playing song info
    $room_id = $this->get_id();
    $conds = [];
    $havings = [];
    $random_online_members = $this->options['random_online_members'];
    $random_positive_vote = $this->options['random_positive_vote'];
    $random_last_played = $this->options['random_last_played'];

    if ($random_online_members) {
      global $config_server_poll_max_executing_time;
      $members = $this->get_members($config_server_poll_max_executing_time);

      foreach ($members as $member) {
        $member_ids[] = $member['user_id'];
      }

      $member_ids = implode(',', $member_ids);
      $conds[] = "user_id IN ($member_ids)";
    }

    if ($random_positive_vote) { $havings[] = "votes >= 0"; }
    if ($random_last_played) {
      $random_last_played = intval($random_last_played);
      if ($random_last_played = 24) {
        $random_last_played = round((time() - strtotime("today")) / 3600);
      }
      $havings[] = "last_played > $random_last_played";
    }

    if ($conds) { $conds = "AND " . implode(" AND ", $conds); } else $conds="";
    if ($havings) { $havings = "HAVING " . implode(" AND ", $havings); } else $havings="";

    $query = "
      SELECT id,
        IFNULL(vote,0) AS votes,
        IFNULL(last_played,0) AS last_played

      FROM weekendv2_list
      LEFT JOIN (
        SELECT song_id, IFNULL(SUM(value),0) AS vote
        FROM weekendv2_votes
        GROUP BY song_id
      ) AS votes USING(song_id)

      LEFT JOIN (
        SELECT DISTINCT(song_id), TIMESTAMPDIFF(HOUR, timestamp, NOW()) AS last_played
        FROM weekendv2_list
        WHERE skip_reason = 'played'
        AND room_id = $room_id
        ORDER BY timestamp DESC
      ) AS hour_diff USING(song_id)

      WHERE weekendv2_list.room_id=$room_id
      AND id<{$this->get_currently_playing_id()}
      AND skip_reason = 'played'
      $conds
      GROUP BY song_id
      $havings
      ORDER BY RAND()
      LIMIT 1
    ";

    Log::debug($query);

    $result = $this->db->query($query);
    if (!$result) {
      return false;
    }
    if (!$row = $this->db->fetch($result)) {
      return false;
    }
    return $row['id'];
  }

  public function set_next_song($Playlist) {
    $next = $this->get_playlist_next_song();
    if ($next !== false) {
      $this->set_currently_playing_id($next);
      $this->generate_update_version();
    } else {
      if ($this->get_admin_random_radio() == "1") {
        // radio is on and list is empty
        $copy_id = $this->get_random_song();
        if ($copy_id) {
          $this->set_currently_playing_id($Playlist->add_copy($copy_id));
          $this->generate_update_version();
        }
      }
    }
  }

  public function set_currently_playing_id($value) {
    $this->db->query("update weekendv2_rooms SET currently_playing_id='{$value}' where id='{$this->get_id()}' LIMIT 1");
  }

  public function generate_update_version() {
    $update_version = md5(microtime() . mt_rand(101,9999999) );
    $this->db->query("update weekendv2_rooms SET update_version='{$update_version}' where id='{$this->get_id()}' LIMIT 1");
  }

  public function logout() {
    global $Users;
    if ($this->is_admin()) {
      $this->set_admin(0);
    }
    $Users->logout();
  }

  public function is_owner() {
    global $Users;
    return $Users->get_auth_email() == $this->owner_email ? true : false;
  }

  public function is_admin() {
    global $Users;
    return $Users->get_auth_id() == $this->admin ? true : false;
  }
}
?>