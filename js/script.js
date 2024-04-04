
jQuery( function() {

    //animate gif images on reload. just add gifanim to class name of img
    var imgsrc = jQuery('.gifanim').attr('src');
    imgsrc = imgsrc + '?a=' + Math.random(); 
    jQuery('.gifanim').attr('src', '');
    jQuery('.gifanim').attr('src', imgsrc);


   /*  jQuery('#datetimepicker').datetimepicker({
        dateFormat: "yy-mm-dd",
        controlType: 'select',
        timeFormat: "hh:mm tt",
        minDate: new Date(),
	    maxDate: new Date(2020, 12)
    }); */

    /* jQuery("#datetimepicker" ).datepicker({
	
        changeMonth: true,
        changeYear: false,
        dateFormat: "yy-mm-dd",
        minDate: new Date(),
        maxDate: "2020-31-12",
        defaultDate: new Date() 
        
    }); */
   
    
    

    





    jQuery('#busy').modal({
        keyboard: false,
        backdrop:true,
        show:0
    })

    jQuery('#busy').on('shown.bs.modal', function() {
        jQuery("body.modal-open").removeAttr("style");
    });
     

    


});



//********************************************************************

	

function readURL(input) {
            if (input.files && input.files[0]) {
				var imgPath = input.files[0].name;
				var extn = imgPath.substring(imgPath.lastIndexOf('.') + 1).toLowerCase();
			    if (extn == "gif" || extn == "png" || extn == "jpg" || extn == "jpeg") {
       			if (typeof (FileReader) != "undefined") {
                var reader = new FileReader();			
                reader.onload = function (e) {
                    jQuery('#rideimg')
                        .attr('src', e.target.result)
                        .width(150)
                        .height('auto');

                   				
						
                };

                reader.readAsDataURL(input.files[0]);
            }
        }
			}
		}
		

        function readURL2(input) {
            if (input.files && input.files[0]) {
				var imgPath = input.files[0].name;
				var extn = imgPath.substring(imgPath.lastIndexOf('.') + 1).toLowerCase();
			    if (extn == "gif" || extn == "png" || extn == "jpg" || extn == "jpeg") {
       			if (typeof (FileReader) != "undefined") {
                var reader = new FileReader();			
                reader.onload = function (e) {
                    jQuery('#receipt')
                        .attr('src', e.target.result)
                        .width(350)
                        .height('auto');						
						
                };

                reader.readAsDataURL(input.files[0]);
            }
        }
			}
        }
        


        /* jQuery('#image-editor').cropit({
            smallImage:'stretch',
            width:300,
            height:300
        });       

        jQuery('.rotate-cw-btn').click(function() {
            jQuery('#image-editor').cropit('rotateCW');
          });
          jQuery('.rotate-ccw-btn').click(function() {
            jQuery('#image-editor').cropit('rotateCCW');
          }); */

     

//*******************cookie functions***************************	

function createCookie(name,value,days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name,"",-1);
}


//****************map*****************************************

    


    function initAutocomplete() {
                            
        var input = document.getElementById('searchTextField');
        var options = {
            componentRestrictions: {country: 'ng'}
        };

        autocomplete = new google.maps.places.Autocomplete(input, options);
      }



  
  




    