var Message = {
    createAutoClosingAlert: function(selector, delay) {
       var alert = $(selector).alert();
       window.setTimeout(function() { alert.alert('close') }, delay);
    },

    addAlert: function(msg, type){
      var alert_class = '';
      switch(type) {
        case 'error':
          alert_class = 'alert-danger';
          break;

        case 'warning':
          alert_class = 'alert-warning';
          break;

        case 'info':
          alert_class = "alert alert-info";
          break;

        default:
          alert_class = 'alert-success';
      }


      jQuery(function () {
        jQuery.notifyBar({
          html: msg,
          delay: 2000,
          cssClass: alert_class,
          animationSpeed: "normal"
        });
      });

    }
};