jQuery(document).ready(function(){
	// some elements we want hidden or unhidden only if java is working
	jQuery("#license-content, #getting_started, #file_download_p").addClass( "hidden", 1000 );
	jQuery("#getting_started_wrap, #type_menu, #lang_menu").removeClass("hidden");
	// This will hide all the file table rows if javascript is working
	jQuery("#file_table tr").addClass("file-item");
	// select which type and language we want active to begin, add active state to selected menu items and choosen state to selected table rows
	var beginningLang = jQuery( "#file_div" ).data( "lang" );
	jQuery("#bible, #"+beginningLang).addClass( "active-state", 1000 );
	jQuery(".bible").addClass( "choosen-type", 1000 );
	jQuery("."+beginningLang ).addClass( "choosen-lang", 1000 );

	// Toggle open/closed the getting started element if button is clicked
	jQuery("#getting_started_button").click(function() {
		jQuery("#getting_started").toggleClass("hidden", 1000);
		jQuery("#getting_started_button").toggleClass("active indent", 1000);
        return false;
	});

	// if any language menu is clicked we want to remove "choosen-lang" from all rows then add it back to to certain rows depending on which button is clicked, finally give the clicked button active status
	jQuery( ".lang-menu-item" ).click(function() {
	  jQuery( ".file-item" ).removeClass( "choosen-lang", 1000 );
	  jQuery(".lang-menu-item").removeClass("active-state", 1000 );
	  var tClass = this.getAttribute( "id" );
      jQuery( "."+tClass ).addClass( "choosen-lang", 1000 );
      jQuery( this ).addClass( "active-state", 1000 );
      return false;
    });
	// if any type menu is clicked we want to remove "choosen-type" from all rows then add it back to to certain rows depending on which button is clicked, finally give the clicked button active status
	jQuery( ".type-menu-item" ).click(function() {
	  jQuery( ".file-item" ).removeClass( "choosen-type", 1000 );
	  jQuery(".type-menu-item").removeClass("active-state", 1000 );
	  var tClass = this.getAttribute( "id" );
      jQuery( "."+tClass ).addClass( "choosen-type", 1000 );
      jQuery( this ).addClass( "active-state", 1000 );
      return false;
    });
	// functionality for "select all" checkboxes
	jQuery( ".select_all" ).click(function() {
	  var tCheck = this.checked;
	  jQuery( this ).addClass("test", 1000);
      jQuery( "tr.choosen-type.choosen-lang input" ).prop( "checked" , tCheck );
    });
	// when submit button is clicked, first click hide all elements accept the license agreement, second click show progress bar then submit form if license is agreed to
    var submit_clicks=0;
	function retrieveFile (){jQuery("#retrieve_files").submit();}
    jQuery( "input:submit" ).click(function() {
	  if (submit_clicks==0) {
      jQuery( ".hidden" ).removeClass( "hidden" , 1000 );	  
      jQuery( ".choosen-type" ).removeClass( "choosen-type" , 1000 );
      jQuery( ".choosen-lang" ).removeClass( "choosen-lang" , 1000 );
      jQuery( "#type_menu, #lang_menu, #getting_started" ).addClass( "hidden" , 1000 );
	  window.location = '#';
	  } else {
	  	if( jQuery( "#license-agreement" ).prop("checked") ){
	  		jQuery("#progressbar").wrap("<div class='ui-widget-overlay'></div>");
	  		jQuery("#progressbar").addClass("ui-front",1000);
	  		jQuery("#progressbar").progressbar({
		    	value: false,
		    });
		    // we need just the smallest delay to makes sure the progress bar loads before we start preparing the files
		    window.setTimeout(retrieveFile, 10);
	  	} else {
	  		jQuery( this ).before( "<p class='error'>You must agree to the license before you can download any files.</p>" );
	  		jQuery("#license-agreement-paragraph").addClass("error_border", 1000);
	  	};
	  };
	  submit_clicks++;
      return false;
   });

	// If files are ready for download create appropriate popup dialog box, on close of box send to donate page
    jQuery( "#files_ready" ).dialog({
      autoOpen:true,
      resizable: false,
      modal: true,
      width: 500,
      title: "Files Ready",
      buttons: {
        Download: function() {
		  var link = jQuery( "#file_download_a" ).attr( "href" );
          window.location=link;
        },
        Close: function() {
          window.location="http://iss.nateude.com/donate/";
        }
      }
    });


});