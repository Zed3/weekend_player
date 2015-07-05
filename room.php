<?php
  require_once "startup.php";
  $error_message = "";
  if (!$Users->is_auth()) {
    header('Location: login.php');
    die();
  }

  $room_id = $_GET["id"];
  if (!$Rooms->room_exists_by_id($room_id)) {
     header('Location: index.php');
     die();
  }

  $room = $Rooms->get_room($room_id);
  $user_permissions = $room->get_user_options();
  $user_id = $Users->get_auth_id();
  $user_permission = $user_permissions[$user_id];


  @$action = $_GET["action"] || null;
  switch ($action) {
    case "logout":
      echo "logout initiated";
      $room->logout();
      header('Location: index.php');
      die();
    break;
  }

  $room_member_list = $room->get_members(666);

  //update admin flag
  // check for admin presence
  $is_admin_on = false;
  $room_admin = $room->options['room_admin'];

  //check that admin is actually on
  foreach ($room_member_list as $member) {
    if (intval($member['user_id']) == $room_admin){
      $is_admin_on = true;
    }
  }

if ($room->is_owner()) {
//TODO: remove  echo "<div>You are owner</div>";
  if (!$room->is_admin()) {
//TODO: remove    echo "<div>Take control</div>";
  }
}
if ($room->is_admin()) {
//TODO: remove  echo "<div>You are admin</div>";
}

  if (!$is_admin_on) {
    $room->set_admin($Users->get_auth_id());
  }

  require("header.php");
?>
<script src="/js/room.js"></script>
<script src="/js/messages.js"></script>
<script src="/js/jquery.notifyBar.js"></script>
<script src="/js/chat.js"></script>
<script>
  /* don't worry it wont help you hack the room, just to save IO */
  is_room_admin = <?php echo $room->is_admin() ? '1' : '0'; ?>;
  room_id = "<?=$room_id?>";
  user_permission = JSON.parse('<?php echo json_encode($user_permission);?>');
</script>
<div class="container-fluid">


  <!-- navigation -->
  <ol class="breadcrumb">
    <li><span class='glyphicon glyphicon-music'></span></li>
    <li><a href="/">Home</a></li>
    <li class="active">Room: <?=$room->get_name()?></li>
    <li class="right"><a href="/room.php?id=<?=$room_id?>&action=logout">Logout</a></li>
<?php
  if ($room->is_admin()) {
    echo '<li class="right">Admin</li>';
  }
?>
  </ol>

  <div class="row">
    <div class="col-xs-12 col-md-8">
<div role="tabpanel">
  <!-- Nav tabs -->
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active"><a href="#now-playing" aria-controls="now-playing" role="tab" data-toggle="tab">Playlist</a></li>
    <li role="presentation"><a href="#chat" aria-controls="chat" role="tab" data-toggle="tab">Chat <span id="chat_message_count" class="badge">0</span></a></li>
    <li role="presentation"><a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">Settings</a></li>
  </ul>

  <!-- Tab panes -->
  <div class="tab-content">
    <div role="tabpanel" class="tab-pane" id="now-playing">
    </div><!-- end of tab-pane -->

    <div role="tabpanel" class="tab-pane" id="chat">
      <div class="panel panel-primary">
        <div class="panel-heading">Chat</div>
        <div class="panel-body" id="chat-container">

          <table class="table table-hover" id="chat-table">
            <tbody id="chat-table-data">
            </tbody>
          </table>

          <div class="input-group">
            <div class="input-group-addon"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></div>
            <input id="chat-text" class="form-control" required autofocus placeholder="Be nice or go away" />
          </div>
        </div><!-- end of panel body -->
        <div class="panel-footer"><span class="small itallic" id="chat-online-list"></span></div>
      </div><!-- end of panel -->
    </div><!-- end of tab-pane -->

    <div role="tabpanel" class="tab-pane" id="settings">
      <div class="panel panel-primary">
        <div class="panel-heading">Shared volume (<span id="admin_volume_count">100</span>):</div>
        <div class="panel-body">
        <input type="range" class="admin_volume_slider" id="player_admin_volume_slider" min="0" max="100" value="100" step="1" onchange="set_admin_volume(this.value)">
      </div>
      </div>

      <div class="panel panel-primary" id="shared_radio">
        <div class="panel-heading">Shared Radio state (<span id="admin_radio_state">Off</span>):</div>
        <div class="panel-body">

<div id="room_options"></div>
<table class="table table-hover table-striped">
  <tbody>

    <tr>
      <td>Play random songs if the list is empty</td>
      <td>
        <label class="radio-inline"><input type="radio" class="" id="player_admin_radio_stateOn" name="player_admin_radio_state" value="1" onchange="set_admin_radio(this.value)" /> On</label>
        <label class="radio-inline"><input type="radio" class="" id="player_admin_radio_stateOff" name="player_admin_radio_state" value="0" checked="checked" onchange="set_admin_radio(this.value)" /> Off</label>
      </td>
    </tr>
    <tr>
      <td>Choose song from online members only</td>
      <td>
        <label class="radio-inline"><input type="radio" name="random_online_members" id="random_online_members_1" value="1" onchange="Room.set_option(this.name, this.value)"> On</label>
        <label class="radio-inline"><input type="radio" name="random_online_members" id="random_online_members_0" value="0" onchange="Room.set_option(this.name, this.value)"> Off</label>
      </td>
    </tr>
    <tr>
      <td>Choose song without negative votes only</td>
      <td>
        <label class="radio-inline"><input type="radio" name="random_positive_vote" id="random_positive_vote_1" value="1" onchange="Room.set_option(this.name, this.value)"> On</label>
        <label class="radio-inline"><input type="radio" name="random_positive_vote" id="random_positive_vote_0" value="0" onchange="Room.set_option(this.name, this.value)"> Off</label>
      </td>
    </tr>
    <tr>
      <td>Choose song that was not played for duration</td>
      <td>
        <label class="radio-inline"><input type="radio" name="random_last_played" id="random_last_played_1" value="1" onchange="Room.set_option(this.name, this.value)"> 1 Hour</label>
        <label class="radio-inline"><input type="radio" name="random_last_played" id="random_last_played_5" value="5" onchange="Room.set_option(this.name, this.value)"> 5 Hours</label>
        <label class="radio-inline"><input type="radio" name="random_last_played" id="random_last_played_24" value="24" onchange="Room.set_option(this.name, this.value)"> Today (since 00:00)</label>
        <label class="radio-inline"><input type="radio" name="random_last_played" id="random_last_played_0" value="0" onchange="Room.set_option(this.name, this.value)"> Off</label>
      </td>
    </tr>
  </tbody>
</table>

      </div>
      </div>

      <div class="panel panel-primary">
        <div class="panel-heading">User Permissions</div>
        <div class="panel-body">
          <?php
            $room_member_list = $room->get_members(9999999);
            $room_user_options = $room->get_user_options();

            if ($room->is_user_allowed_to($user_id, 'can_change_permissions') || 1==1) { //TODO: fix this
          ?>
          <form>
          <table class="table table-hover table-striped">
            <thead>
              <tr>
                <th></th>
                <?php
                  // foreach ($room_member_list as $member_info) {
                  //   echo "<th>" . $member_info['member_name'] . "</th>";
                foreach ($room->user_permission_array as $key => $title) {
                  echo "<th>$title</th>";
                  }
                ?>
              </tr>
            </thead>
            <tbody>
              <?php
                  foreach ($room_member_list as $member_info) {
                  echo "<tr><td>" . $member_info['member_name'] . "</td>";
                    foreach ($room->user_permission_array as $key => $title) {
                    $user_id = $member_info['user_id'];
                    @$db_value = $room_user_options[$user_id][$key];
                    $checked = $db_value == true ? " checked='checked'" : "";
                    echo "<td>";
                    echo "<label class='checkbox-inline'><input type='checkbox' $checked name='$key' value='1' onchange='Room.set_user_option(\"$key\", $user_id , this.value)'> </label>";
                    echo "</td>";
                  }
                  echo "</tr>";
                }
              ?>
            </tbody>
          </table>
        </form>
          <?php
            } else {
              echo "<p>You have no permission to this section</p>";
            }
          ?>

        </div><!-- end of panel-body -->
      </div><!-- end of panel -->



      <div class="panel panel-primary">
        <div class="panel-heading">Player size (1-3):</div>
        <div class="panel-body">
          <input type="range" class="player_size_slider" min="1" max="3" value="1" step="1" onchange="set_player_size(this.value)">
        </div>
      </div>
    </div><!-- end of tab-pane -->

  </div> <!-- end of tab content -->
</div><!-- end of tab-panel -->

      <div class="panel panel-primary">
        <div class="panel-heading">Playlist</div>
        <div class="panel-body" id="playlist-container">

          <table class="table table-hover table-striped" id="history-table">
            <tbody id="history-table-data">
            </tbody>
          </table>

        </div><!-- end of panel body -->
      </div><!-- enf of panel -->

    </div><!--end of main div -->

    <div class="col-xs-6 col-md-4">

      <div class="panel panel-primary">
        <div class="panel-heading">Add New Link</div>
          <div class="panel-body">
            <div id="div_loading_area" class="add_new_form_loading add_new_form_loading_hide"><img src="ajax-loader.gif"></div>
            <p>Add new YouTube video Url, or just search...</p>
            <div class="input-group">
              <input id="url_youtube" type="url" class="form-control" placeholder="https://www.youtube.com/watch?v=..." required autofocus>
              <span class="input-group-btn">
                <button class="btn btn-default" type="button" onclick="add_youtube_video($('input[id=url_youtube]')[0].value);$('input[id=url_youtube]')[0].value=''">Add</button>
              </span>
            </div><!-- /input-group -->
            <div id="search_results"></div>
          </div><!-- panel body -->
      </div><!-- panel -->

      <div class="panel panel-primary">
        <div class="panel-heading">Top Contributers</div>
          <div class="panel-body" id="stats_contributers">
          </div><!-- panel body -->
      </div><!-- panel -->

      <div class="panel panel-primary">
        <div class="panel-heading">Now Playing
        | <a href='#' class="white"><span class='glyphicon glyphicon-fast-forward' aria-hidden='true' onclick='user_action(["can_change_song"])'></span></a>
        | <a href='#' class="white" id='player-pause-play'><span class='glyphicon glyphicon-pause' aria-hidden='true' onclick='Room.set_option("player_status", "pause")'></span></a>
        | <a href='#' class="white" value='true'  id='player-mute'><span class='glyphicon glyphicon-volume-up' aria-hidden='true' onclick='Room.set_option("player_status", "mute")'></span></a>

<!--
        | <a href='#' class="white"><span class='glyphicon glyphicon-fast-forward' aria-hidden='true' onclick='user_action(["can_change_song"])'></span></a>
        | <a href='#' class="white" id='player-pause-play'><span class='glyphicon glyphicon-pause' aria-hidden='true' onclick='toggle_player("pause")'></span></a>
        | <a href='#' class="white" value='true'  id='player-mute'><span class='glyphicon glyphicon-volume-up' aria-hidden='true' onclick='toggle_player("mute")'></span></a>

-->
        </div>
          <div class="panel-body">
            <div id="player"></div>
          </div><!-- panel body -->
      </div><!-- panel -->

    </div><!-- end of sidebar div -->
  </div><!-- end of row -->


  <span class="developer"><span class="flag-israel"></span>MADE IN ISRAEL, FUELED BY COFFEE &amp; GOOD MUSIC</span>

</div><!-- end of container -->


<div class="room_container">



  <div class="room_panels">
    <div class="panel panel-primary">
      <div class="panel-heading">room members (<span id="room_members_list_head_count">0</span>):</div>
      <div class="panel-body">
        <ul id="room_members_list" class="list-group"></ul>
      </div>
    </div>
  </div><!-- end of panels -->
  <div class="room_main">




          <h4>History (10 last videos):</h4>
          <div id="div_history"></div>
          <h4>Currently playing: <span id="current_song_title"></span></h4>
          <h4>Playing next:</h4>
          <div id="div_playlist"></div>



          <div class="bottom_spacer"></div>

  </div>
</div>
<?php require("footer.php"); ?>
