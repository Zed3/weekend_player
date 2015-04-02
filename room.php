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

require("header.php");
?>
<script src="/js/room.js"></script>
<script src="/js/chat.js"></script>
<script>
/* don't worry it wont help you hack the room, just to save IO */
is_room_admin = <?=($Users->get_auth_email() == $room->get_owner_email() ? "true" : "false")?>;
room_id = "<?=$room_id?>";
</script>

<div class="pinpoint_rulez">Pinpoint Rulez.</div>

<div class="room_container">

<div class="stats" id="stats-container">
  <div class="panel panel-primary">
    <div class="panel-heading">Top Contributers</div>
      <div id="stats_contributers">
      </div>
  </div>

  <div class="panel panel-primary">
    <div class="panel-heading">Playlist</div>
    <div class="panel-body" id="playlist-container">

      <table class="table table-hover">
        <tbody id="history-table">
        </tbody>
      </table>

    </div>
  </div>
</div>

  <div class="room_panels">
    <div class="panel panel-primary">
      <div class="panel-heading">Shared volume (<span id="admin_volume_count">100</span>):</div>
      <div class="panel-body">
      <input type="range" class="admin_volume_slider" id="player_admin_volume_slider" min="0" max="100" value="100" step="1" onchange="set_admin_volume(this.value)">
    </div>
    </div>
    <div class="panel panel-primary">
      <div class="panel-heading">Shared Radio state (<span id="admin_radio_state">Off</span>):</div>
      <div class="panel-body">
      <input type="radio" class="admin_radio_state" id="player_admin_radio_stateOff" name="player_admin_radio_state" value="0" checked="checked" onchange="set_admin_radio(this.value)" />
      <label for="0">Off</label>
      <input type="radio" class="admin_radio_state" id="player_admin_radio_stateOn" name="player_admin_radio_state" value="1" onchange="set_admin_radio(this.value)" />
      <label for="1">On</label>
      <div style="
          font-size: 9pt;
          text-align: center;
      ">( play random songs from the history when the list is empty, this is a shared option and not private. )</div>
    </div>
    </div>
    <div class="panel panel-primary">
      <div class="panel-heading">Player size (1-3):</div>
      <div class="panel-body">
        <input type="range" class="player_size_slider" min="1" max="3" value="1" step="1" onchange="set_player_size(this.value)">
      </div>
    </div>
    <div class="panel panel-primary">
      <div class="panel-heading">room members (<span id="room_members_list_head_count">0</span>):</div>
      <div class="panel-body">
        <ul id="room_members_list" class="list-group"></ul>
      </div>
    </div>
  </div>
  <div class="room_main">
    <ol class="breadcrumb">
      <li><a href="/">Home</a></li>
      <li class="active">Room: <?=$room->get_name()?></li>
    </ol>

    <h4>History (10 last videos):</h4>
    <div id="div_history"></div>
    <h4>Currently playing: <span id="current_song_title"></span></h4>
    <div id="player"></div>
    <h4 class="add_new_form">
      <div id="div_loading_area" class="add_new_form_loading add_new_form_loading_hide"><img src="ajax-loader.gif"></div>
      <div>Add new (youtube video url):</div>
      <input id="url_youtube" type="url" class="form-control" placeholder="https://www.youtube.com/watch?v=..." style="width: 500px;display:inline;" required autofocus>
      <button class="btn btn-lg btn-primary" type="button" onclick="add_youtube_video($('input[id=url_youtube]')[0].value);$('input[id=url_youtube]')[0].value=''">Add</button>
    </h4>

    <h4>Playing next:</h4>
    <div id="div_playlist"></div>
    <div class="bottom_spacer"></div>
  </div>
</div>

<? require("footer.php"); ?>
