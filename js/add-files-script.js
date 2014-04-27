jQuery(document).ready(function(){
    jQuery( ".sortable" ).sortable({
      placeholder: "ui-state-highlight",
      axis: "y",
      cursor: "s-resize"
    });
    jQuery( ".tabs" ).tabs();
});