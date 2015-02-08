<?
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
<script src="room.js"></script>
<script>
/* don't worry it wont help you hack the room, just to save IO */
is_room_admin = <?=($Users->get_auth_email() == $room->get_owner_email() ? "true" : "false")?>;
room_id = "<?=$room_id?>";
</script>

<div class="pinpoint_rulez">Pinpoint Rulez.</div>

<div class="room_container">
  <div class="room_panel">
    <div class="room_members_list">
      <div id="room_members_list_head">room members (<span id="room_members_list_head_count">0</span>):</div>
      <ul id="room_members_list"></ul>
    </div>
  </div>
  <div class="room_main">
    <h4><a href="index.php">Back to room list</a></span></h4>

    <h1 style="text-decoration: underline;">Room: <?=$room->get_name()?></h1>
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
  </div>
</div>

<? require("footer.php"); ?>
