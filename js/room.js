var mark_play_next = true;
var timer_play_next = null;
var last_sync_time = 0;
var is_room_admin = false;
var room_id = "";
var DT_update_version = "";
var DT_currently_playing_id = "";
var DT_currently_playing_data = {};
var tag = document.createElement('script');
var admin_volume_monitor = null;
var admin_volume_last_volume = 100;
var search_results = "", local_search_results = "";
tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
var player;

function update_search_results() {
    $("#search_results").html("<div class='list-group'>" + local_search_results + search_results + "</div>");
}

$(document).ready(function() {
    //Create dynamic youtube search
    $("#url_youtube").keyup(function(){
        var search_input = $(this).val();
        var keyword = search_input;
        var max_results = 5;

        // Youtube API
        var yt_url='http://gdata.youtube.com/feeds/api/videos?q=' + keyword + '&format=5&max-results=' + max_results + '&v=2&alt=jsonc';
        if (keyword.length == 0){
            search_results = "";
            local_search_results = "";
            update_search_results();
        }
        if (keyword.length < 5){ return; }


        $.ajax({
            url: "server.php?" + generate_ajax_key(),
            type: "POST",
            data: {
                "id": room_id,
                "task": "client",
                "kind": "song_search",
                "keyword": keyword
            },
            dataType: "json",
            success: function(response){
                if(response){
                    local_search_results = "";
                    $.each(response, function(i,data){
                        var video_id = data.v;
                        var video_title = data.title;

                        //var title = video_title + ": " + length_to_time(data.duration);
                        var title = video_title;
                        if (data.local) {
                            title += " <span class='glyphicon glyphicon-ok'></span>";
                        }
                        var youtube_url = "https://www.youtube.com/watch?v=" + data.v;
                        local_search_results += "<a href='#' class='list-group-item' onclick='add_youtube_video(\"" + youtube_url + "\")'>" + title + "</a>";
                        update_search_results();
                    });
                }
            },
            timeout: 60000
        });

//         $.ajax({
//             type: "GET",
//             url: yt_url,
//             dataType:"jsonp",
//             success: function(response){
//                 if(response.data.items){
//                     search_results = "";
//                     $.each(response.data.items, function(i,data){
//                         var video_id = data.id;
//                         var video_title = data.title;
//                         var video_viewCount = data.viewCount;
//                         var youtube_url = "https://www.youtube.com/watch?v=" + data.id;
//                         search_results += "<a href='#' class='list-group-item' onclick='add_youtube_video(\"" + youtube_url + "\")'>" + video_title + ": " + length_to_time(data.duration) + "</a>";
//                     });

//                     var re = new RegExp(keyword, 'gi');
// //                    results = results.replace(re, "<span class='text-info'>" + keyword + "</span>");
//                     update_search_results();
//                 } else {

//                 }
//             }
//         });
    });

////////////////////


} );
var Room = {
    members: [],
    set_option: function (key, value) {
        $("#shared_radio .panel-heading").append('<span id="load">__</span>');
        $('#load').fadeIn('normal');
        $.ajax({
            url: "server.php?" + generate_ajax_key(),
            type: "POST",
            data: {
                "id": room_id,
                "task": "client",
                "kind": "update_option",
                "key": key,
                "value": value
            },
            dataType: "json",
            timeout: 60000
        });
    },
    set_user_option: function (key, user_id, value) {
        // $("#shared_radio .panel-heading").append('<span id="load">__</span>');
        // $('#load').fadeIn('normal');
        $.ajax({
            url: "server.php?" + generate_ajax_key(),
            type: "POST",
            data: {
                "id": room_id,
                "task": "client",
                "kind": "update_user_option",
                "key": key,
                "user_id": user_id,
                "value": value
            },
            dataType: "json",
            timeout: 60000
        });
    }
};

function onYouTubeIframeAPIReady() {
    player = new YT.Player('player', {
        height: '360',
        width: '570',
        videoId: 'M7lc1UVf-VE',
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange,
            'onError': onPlayerError
        }
    });
}

function set_player_size(size) {
    var frame = $("#player")[0];
    switch (size) {
        case "1":
            frame.width = 640;
            frame.height = 390;
        break;
        case "2":
            frame.width = 800;
            frame.height = 600;
        break;
        case "3":
            frame.width = 1024;
            frame.height = 820;
        break;
    }
}


function onPlayerReady(event) {
    if (is_room_admin) {
        player.unMute();
        admin_volume_monitor = setTimeout(monitor_admin_volume, 1000);
    } else {
        player.mute(); // mute by default for none admin users.. changeable by clicking on unmute in the player
    }
    doPolling();
}

function onPlayerError(event) {
    var reason = "";
    switch (event.data) {
        case "2":
            reason = "The request contains an invalid parameter value";
            break;
        case "100":
            reason = "The video requested was not found";
            break;
        case "101":
        case "150":
        default:
            reason = "The owner of the requested video does not allow it to be played in embedded players";
            break;
    }
    console.log("error: " + event.data, reason);
    admin_report("player_error", reason);
}

function onPlayerStateChange(event) {
    console.log("stateChanged : " + event.data);
    switch (event.data) {
        case -1:
            // unstarted
            break;
        case YT.PlayerState.PLAYING:

            break;
        case YT.PlayerState.PAUSED:

            break;
        case YT.PlayerState.ENDED:
            admin_report("player_end");
            break;
        case YT.PlayerState.BUFFERING:
            break;
        case YT.PlayerState.CUED:
            break;
    }
}

function playVideo(id) {
    _loadVideo(id);
    _playVideo();
}

function _loadVideo(id) {
    player.loadVideoById(id, 0)
}

function _playVideo() {
    player.playVideo();
}

function _stopVideo() {
    player.stopVideo();
}
var unloading_page = false;
var last_poll_request = null;

function doPolling() {
    (function poll_data() {
        if (unloading_page) {
            return;
        }
        last_poll_request = $.ajax({
            url: "server.php?" + generate_ajax_key(),
            type: "POST",
            data: {
                "id": room_id,
                "task": "poll",
                "update_version": DT_update_version
            },
            success: function(data) {
                parsePollingData(data);
            },
            dataType: "json",
            complete: function() {
                setTimeout(poll_data, 1000);
            },
            timeout: 15000
        });
    })();
}

window.onbeforeunload = function() {
    unloading_page = true;
    try {
        _stopVideo();
        last_poll_request.abort();
    } catch (e) {}
}

function generate_ajax_key() {
    return Date.now() + "." + Math.random() * Date.now();
}

function parsePollingData(data) {
    if (!data || data["timeout"] == true) {
        if (data) {
            if (data["members"]) {
                redraw_members_list(data["members"]);
            }
        }
        return;
    }

    //TODO: Need to move it to outer, more general controller
    if (data["chat"]) {
        Chat.update_chat(data["chat"]);
    }

    if (data["members"]) {
        //TODO: fix this
        Room.members = data["members"];
        Chat.update_online_users(Room.members);
    }

    DT_update_version = data["update_version"];
    var currently_playing_id = data["currently_playing_id"];
    var playlist = data["playlist"];
    var history = data["history"];
    var members = data["members"];
    for (var i in playlist) {
        var one = playlist[i];
        if (one["id"] == currently_playing_id) {
            is_song_changed(currently_playing_id, one);
            break;
        }
    }
    update_lists_info(playlist, history, members);
    redraw_admin_volume(data["admin_volume"]);
    update_stats(data["stats"]);
    update_options(data["room_options"]);
    redraw_admin_radio(data["admin_radio"]);
}

function update_options(options){
    for (var setting in options) {
        var element_id = setting + '_' + options[setting];
        //TODO: currently supports radios only
        $("#" + element_id)[0].checked = "checked";
    }
    $('#load').fadeOut('normal');
}
function toggle_player(action) {
    switch (action) {
        case "play":
            player.playVideo();
            $('#player-pause-play').html("<span class='glyphicon glyphicon-pause' aria-hidden='true' onclick='toggle_player(\"pause\")'></span>");
        break;
        case "pause":
            player.pauseVideo();
            $('#player-pause-play').html("<span class='glyphicon glyphicon-play' aria-hidden='true' onclick='toggle_player(\"play\")'></span>");
        break;
        case "mute":
            player.mute();
            $('#player-mute').html("<span class='glyphicon glyphicon-volume-off' aria-hidden='true' onclick='toggle_player(\"unmute\")'></span>");
        break;
        case "unmute":
            player.unMute();
            $('#player-mute').html("<span class='glyphicon glyphicon-volume-up' aria-hidden='true' onclick='toggle_player(\"mute\")'></span>");
        break;
    }
}

function update_stats(data) {
    var content = "";
    for (var i in data) {
        var name = data[i]["name"],
            total = data[i]["total_uploaded"];

        content += "<tr><td>" + name + "</td><td>" + total + "</td></tr>";

    }

    var string = "<!-- Table --><table class='table'><thead><tr><th>Name</th><th>Total</th></tr></thead><tbody>" + content + "</tbody></table>";
    $("#stats_contributers").html(string);
}

function redraw_admin_volume(volume) {
    $("#player_admin_volume_slider")[0].value = volume;
    $("#admin_volume_count")[0].innerText = volume;
    if (is_room_admin) {
        player.setVolume(volume);
    }
}

function set_admin_volume(volume) {
    if (is_room_admin) {
        redraw_admin_volume(volume); // update admin ui
    }
    $.ajax({
        url: "server.php?" + generate_ajax_key(),
        type: "POST",
        data: {
            "id": room_id,
            "task": "client",
            "kind": "update_volume",
            "volume": volume
        },
        dataType: "json",
        timeout: 60000
    });
}

function monitor_admin_volume() {
    // invokes every second
    var current_volume = player.getVolume();
    if (current_volume != admin_volume_last_volume) {
        admin_volume_last_volume = current_volume;
        set_admin_volume(current_volume); // update server
    }
}

function set_admin_radio(state) {
    $.ajax({
        url: "server.php?" + generate_ajax_key(),
        type: "POST",
        data: {
            "id": room_id,
            "task": "client",
            "kind": "update_radio",
            "radio": state
        },
        dataType: "json",
        timeout: 60000
    });
}

function redraw_admin_radio(state) {
    if (state == "1") {
        $("#player_admin_radio_stateOn")[0].checked = "checked";
    } else {
        $("#player_admin_radio_stateOff")[0].checked = "checked";
    }
    $("#admin_radio_state")[0].innerText = (state == "1" ? "On" : "Off");
}

function is_song_changed(id, data) {
    if (id == DT_currently_playing_id) {
        return
    }
    DT_currently_playing_id = id;
    DT_currently_playing_data = data;
    playVideo(data["v"]);
    update_current_info(data);
}

function update_lists_info(playlist, history, members) {
    $("#div_history")[0].innerHTML = "";
    $("#div_playlist")[0].innerHTML = "";
    redraw_list($("#div_history")[0], history, false);
    redraw_list($("#div_playlist")[0], playlist, true);

    create_table_data($("#history-table-data")[0], playlist.concat(history));

    redraw_members_list(members);
}

function create_inline_div(text, className, title) {
    var el = $("<div>")[0];
    el.innerHTML = text;
    if (title) {
        el.title = title;
    }
    el.className = className || "";
    el.className += " item_inline_div";
    return el;
}

function redraw_members_list(members) {
    // last_update, member_email, member_name
    var container = $("#room_members_list")[0];
    // clear old data
    container.innerHTML = "";

    for (var i in members) {
        var one = members[i];
        var li = $("<li class='list-group-item'>")[0];
        li.innerText = li.textContent = one["member_name"];
        li.title = one["member_email"] + " (last update before " + one["last_update"] + " seconds)";
        container.appendChild(li);
    }
    $("#room_members_list_head_count")[0].innerText = members.length;
}



function truncate(string){
   if (string.length > 200)
      return string.substring(0,200) + '...';
   else
      return string;
}

function create_table_data(table, list) {
    var table_row = "";
    for (var i in list) {
        var one = list[i];
        var id = one["id"];
        var tr_class = DT_currently_playing_id == id ? ' class="info" ' : '';
        var v = one["v"];
        var title = one["title"];

        title = this.truncate(title);
        if (DT_currently_playing_id == id) {
            title = "<span class='glyphicon glyphicon-play'></span> " + title;
        }

        var song_id = one["song_id"];
        var datetime = one["datetime"];
        var votes = one["votes"];
        var total_played = one["total_played"];
        var skip_reason = one["skip_reason"];
        var user_name = one["user_name"];
        var length = one["length"];
        var copy = one["copy"];
        if (copy === '1') { title += ' <span class="label label-default">Bot</span>' };

    var youtube_url = "https://www.youtube.com/watch?v=" + v;
    var buttons = "";

    var requeue = DT_currently_playing_id > parseInt(id) ? " <a href='#'><span class='glyphicon glyphicon-repeat btn-xs' aria-hidden='true' onclick='add_youtube_video(\"" + youtube_url + "\")'></span></a>" : "";
    var remove = DT_currently_playing_id < parseInt(id) ? " <a href='#'><span class='glyphicon glyphicon-stop btn-xs' aria-hidden='true' onclick='user_action([\"remove_song\", " + id + "])' ></span></a>" : "";

    table_row += "<tr" + tr_class + ">" +
        "<td>" + title + requeue + remove + "</td><td>" + total_played + "</td><td>" + length_to_time(length) + "</td><td>" + user_name + "</td>" +
        "<td>" +
          "<a href='#'><span class='glyphicon glyphicon-thumbs-up' aria-hidden='true' onclick='vote_video(" + song_id + ", 1)'></span></a>" +
          " <span class='badge'>" + votes + "</span> " +
          "<a href='#'><span class='glyphicon glyphicon-thumbs-down' aria-hidden='true' onclick='vote_video(" + song_id + ", -1)'></span></a>" +
        "</td>" +
      "</tr>";

    }
    $("#history-table-data").html(table_row);
}

function redraw_list(div, list, inc_counter) {

    var counter = inc_counter ? 0 : list.length + 1;
    for (var i in list) {
        var one = list[i];
        var id = one["id"];
        if (DT_currently_playing_id == id) {
            continue;
        }
        inc_counter ? counter++ : counter--;
        var v = one["v"];
        var title = one["title"];
        var song_id = one["id"];
        var added_by_email = one["added_by_email"];
        var datetime = one["datetime"];
        var votes = one["votes"];
        var skip_reason = one["skip_reason"];
        var user_name = one["user_name"];
        var length = one["length"];
        var copy = one["copy"];
        var youtube_url = "https://www.youtube.com/watch?v=" + v;

        var div_container = $("<div>")[0];
        div_container.appendChild(create_inline_div(counter + "."));
        div_container.appendChild(create_inline_div("<a target='_blank' href='" + youtube_url + "'>" + title + "</a>", "room_list_title"));
        div_container.appendChild(create_inline_div("[" + length_to_time(length) + "]", "room_song_time"));
        div_container.appendChild(create_inline_div("(" + user_name + ")", "", added_by_email));
        if (copy && copy == "1") {
            div_container.appendChild(create_inline_div("(duplication, added by Radio)", "item_inline_copy"));
        }
        if (skip_reason && skip_reason != "played") {
            div_container.appendChild(create_inline_div("(" + skip_reason + ")", "item_inline_skipreason"));
        }
    //add votes
    var buttons = "";
    buttons += ' | ';
    buttons += '<a href="#"><span class="glyphicon glyphicon-thumbs-up" aria-hidden="true" onclick="vote_video(' + song_id + ', 1)"></span></a>';
    buttons += ' <span class="badge">' + votes + '</span> ';
    buttons += '<a href="#"><span class="glyphicon glyphicon-thumbs-down" aria-hidden="true" onclick="vote_video(' + song_id + ', -1)"></span></a>';

    if (is_room_admin) {
        buttons += ' | ';
        buttons += '<a href="#"><span class="glyphicon glyphicon-remove" aria-hidden="true" onclick="remove_video(' + song_id + ')"></span></a>';
    }
    div_container.appendChild(create_inline_div(buttons));

        div.appendChild(div_container);
    }
}

function update_current_info(one) {
    $("#current_song_title")[0].innerHTML = "";
    var v = one["v"];
    var title = one["title"];
    var added_by_email = one["added_by_email"];
    var datetime = one["datetime"];
    var skip_reason = one["skip_reason"];
    var user_name = one["user_name"];
    var length = one["length"];

    var container = $("#current_song_title")[0];
    container.appendChild(create_inline_div(title, "room_current_title"));
    container.appendChild(create_inline_div("[" + length_to_time(length) + "]", "room_song_time"));
    container.appendChild(create_inline_div("(" + user_name + ")", "", added_by_email));
}

function length_to_time(length) {
    length = length + "";
    return length.toHHMMSS();
}

String.prototype.toHHMMSS = function() {
    var sec_num = parseInt(this, 10); // don't forget the second param
    var hours = Math.floor(sec_num / 3600);
    var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
    var seconds = sec_num - (hours * 3600) - (minutes * 60);

    if (hours < 10) {
        hours = "0" + hours;
    }
    if (minutes < 10) {
        minutes = "0" + minutes;
    }
    if (seconds < 10) {
        seconds = "0" + seconds;
    }
    var time = (hours != "00" ? hours + ':' : "") + minutes + ':' + seconds;
    return time;
}

function user_action(params) {
    $.ajax({
        url: "server.php?" + generate_ajax_key(),
        type: "POST",
        data: {
            "id": room_id,
            "task": "user_action",
            "params": params
        },
        success: function(res) {
            // no return data
        },
        dataType: "json",
        timeout: 60000
    });
}

function admin_report(kind, reason) {
    if (!is_room_admin) {
        return;
    }
    if (!reason) {
        reason = "";
    }
    $.ajax({
        url: "server.php?" + generate_ajax_key(),
        type: "POST",
        data: {
            "id": room_id,
            "task": "report",
            "kind": kind,
            "reason": reason
        },
        success: function(data) {
            // no return data
        },
        dataType: "json",
        timeout: 60000
    });
}

function remove_video(video_id) {
    $.ajax({
        url: "server.php?" + generate_ajax_key(),
        type: "POST",
        data: {
            "id": room_id,
            "video_id": video_id,
            "task": "client",
            "kind": "remove"
        },
        success: function(data) {
            doPolling();
        },
        dataType: "json",
        timeout: 60000
    });
}

function vote_video(video_id, vote) {
    $.ajax({
        url: "server.php?" + generate_ajax_key(),
        type: "POST",
        data: {
            "id": room_id,
            "video_id": video_id,
            "vote": vote,
            "task": "client",
            "kind": "vote"
        },
        success: function(data) {
        },
        dataType: "json",
        timeout: 60000
    });
}

function add_youtube_video(address) {
    var v = extract_url_yt(address);
    if (!v) {
        alert("The video url is invalid");
        return;
    }

    $("#div_loading_area").removeClass("add_new_form_loading_hide");
    $.ajax({
        url: "server.php?" + generate_ajax_key(),
        type: "POST",
        data: {
            "id": room_id,
            "task": "client",
            "kind": "add",
            "video_id": v
        },
        complete: function(data) {
            $("#div_loading_area").addClass("add_new_form_loading_hide");
            $("#url_youtube").val('');
            $("#search_results").html('');

            var result = data.responseJSON;
            if (result.error) {
                Message.addAlert(result.error, "error");
            }

            if (result.result == false) {
                Message.addAlert("Could not add song", "warning");
            } else if (result.result == true) {
                Message.addAlert("Added song", "success");
            }
        },
        dataType: "json",
        timeout: 60000
    });
}

function extract_url_yt(url) {
    var expr = /[a-zA-Z0-9\-\_]{11}/;
    var result = expr.exec(url);
    if (result.length > 0) {
        return result[0];
    }
    return false;
}
