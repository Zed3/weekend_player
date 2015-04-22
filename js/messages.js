var Message = {
    createAutoClosingAlert: function(selector, delay) {
       var alert = $(selector).alert();
       window.setTimeout(function() { alert.alert('close') }, delay);
    }
};