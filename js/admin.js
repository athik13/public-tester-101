
 jQuery( function() {

    /* jQuery('.currency-add').fancybox({	
        'width'		: 600,
        'height'	: 400,
        'type'		: 'inline',
            'autoScale'    	: true,
            closeBtn: 0
        
    }); */
        
    $('.select-box').select2(); //initialize select2 inputs 
        
     jQuery('#booking-customer').autocomplete({
            source: function(req,res){
                var post_data = {'action':'customersautocomp','term' : req.term};
                var search_data = [];
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    crossDomain:true,
                    xhrFields: {withCredentials: true},
                    data: post_data,
                    success: function (data, status)
                    {
                                                                        
                        console.log(data);
                        res(data);
            
                    },
                    error:function(jqXHR,textStatus, errorThrown){
                        res();
                    }
                    
                });
            },
            select:function(event,ui){
                jQuery(this).val(ui.item.label );
                jQuery('#booking-customerid').val(ui.item.value );
                return false;
            },
            minLength:1

     });


     jQuery('#booking-customer2').autocomplete({
        source: function(req,res){
            var post_data = {'action':'customersautocomp','term' : req.term};
            var search_data = [];
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                crossDomain:true,
                xhrFields: {withCredentials: true},
                data: post_data,
                success: function (data, status)
                {
                                                                    
                    console.log(data);
                    res(data);
        
                },
                error:function(jqXHR,textStatus, errorThrown){
                    res();
                }
                
            });
        },
        select:function(event,ui){
            jQuery(this).val(ui.item.label );
            jQuery('#booking-customerid2').val(ui.item.value );
            return false;
        },
        minLength:1

 });



     jQuery('#booking-staff').autocomplete({
        source: function(req,res){
            var post_data = {'action':'staffautocomp','term' : req.term};
            var search_data = [];
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                crossDomain:true,
                xhrFields: {withCredentials: true},
                data: post_data,
                success: function (data, status)
                {
                                                                    
                    console.log(data);
                    res(data);
        
                },
                error:function(jqXHR,textStatus, errorThrown){
                    res();
                }
                
            });
        },
        select:function(event,ui){
            jQuery(this).val(ui.item.label );
            jQuery('#booking-staffid').val(ui.item.value );
            return false;
        },
        minLength:1

    });


    jQuery('#booking-staff2').autocomplete({
        source: function(req,res){
            var post_data = {'action':'staffautocomp','term' : req.term};
            var search_data = [];
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                crossDomain:true,
                xhrFields: {withCredentials: true},
                data: post_data,
                success: function (data, status)
                {
                                                                    
                    console.log(data);
                    res(data);
        
                },
                error:function(jqXHR,textStatus, errorThrown){
                    res();
                }
                
            });
        },
        select:function(event,ui){
            jQuery(this).val(ui.item.label );
            jQuery('#booking-staffid2').val(ui.item.value );
            return false;
        },
        minLength:1

    });



     jQuery('#booking-driver').autocomplete({
        source: function(req,res){
            var post_data = {'action':'driversautocomp','term' : req.term};
            var search_data = [];
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                crossDomain:true,
                xhrFields: {withCredentials: true},
                data: post_data,
                success: function (data, status)
                {
                                                                    
                    console.log(data);
                    res(data);
        
                },
                error:function(jqXHR,textStatus, errorThrown){
                    res();
                }
                
            });
        },
        select:function(event,ui){
            jQuery(this).val(ui.item.label );
            jQuery('#booking-driverid').val(ui.item.value );
            return false;
        },
        minLength:1

 });


 jQuery('#booking-driver2').autocomplete({
    source: function(req,res){
        var post_data = {'action':'driversautocomp','term' : req.term};
        var search_data = [];
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            crossDomain:true,
            xhrFields: {withCredentials: true},
            data: post_data,
            success: function (data, status)
            {
                                                                
                console.log(data);
                res(data);
    
            },
            error:function(jqXHR,textStatus, errorThrown){
                res();
            }
            
        });
    },
    select:function(event,ui){
        jQuery(this).val(ui.item.label );
        jQuery('#booking-driverid2').val(ui.item.value );
        return false;
    },
    minLength:1

});



 jQuery('#all-booking-driver').autocomplete({
    source: function(req,res){
        var post_data = {'action':'driversautocomp','term' : req.term,'bookrecsearch' : 1};
        var search_data = [];
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            crossDomain:true,
            xhrFields: {withCredentials: true},
            data: post_data,
            success: function (data, status)
            {
                                                                
                console.log(data);
                res(data);
    
            },
            error:function(jqXHR,textStatus, errorThrown){
                res();
            }
            
        });
    },
    select:function(event,ui){
        jQuery(this).val(ui.item.label );
        jQuery('#booking-driverid').val(ui.item.value );
        return false;
    },
    minLength:1

});






    
  
    jQuery('#datepickerbsearch').datepicker({
        format: "yyyy-mm-dd"
    });


    jQuery('.datepickerinput').datepicker({
        format: "yyyy-mm-dd"
    });

   
    jQuery('.msg-drvr').on('click', function(e){

        var driver_id = jQuery(this).data('drvrid');        
                  
        swal({
            title: "Send Message",
            text: "Enter your message below",
            type: "input",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Send",
            cancelButtonText: "Cancel",
            inputPlaceholder: "Write something",
            closeOnConfirm: false,
            closeOnCancel: true
            },
            function(inputValue){
                if (inputValue === false) return false;

                if (inputValue === "") {
                    swal.showInputError("Please write something!");
                    return false
                }

                //send message through AJAX
                var post_data = {'action':'messagedriver','driver_id' : driver_id, 'content' : inputValue};
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    timeout : 10000,
                    crossDomain:true,
                    xhrFields: {withCredentials: true},
                    data: post_data,
                    success: function (data, status)
                    {
                            
                        try{
                            var data_obj = JSON.parse(data);
                        }catch(e){
            
                            imgurl = '../img/info_.gif?a=' + Math.random();
    
                            swal({
                                        title: '<h1>Error</h1>',
                                        text: 'Failed to send message!',
                                        imageUrl:imgurl,
                                        html:true
                            });

                        }
            
                        
                        if(data_obj.hasOwnProperty('error')){
                            imgurl = '../img/info_.gif?a=' + Math.random();
    
                            swal({
                                        title: '<h1>Error</h1>',
                                        text: 'Failed to send message!',
                                        imageUrl:imgurl,
                                        html:true
                            });
                        }
                        
                        
                        if(data_obj.hasOwnProperty('success')){

                            imgurl = '../img/success_.gif?a=' + Math.random();
    
                            swal({
                                        title: '<h1>Success</h1>',
                                        text: data_obj.success,
                                        imageUrl:imgurl,
                                        html:true
                            });
                            
                            
                        } 
                        
                        

                        
            
            
                    },
                    error: function(jqXHR,textStatus, errorThrown) {  
                        
                        imgurl = '../img/info_.gif?a=' + Math.random();
    
                        swal({
                                    title: '<h1>Error</h1>',
                                    text: 'Failed to send message!',
                                    imageUrl:imgurl,
                                    html:true
                        });
                        
                    }
                    
                });

                
             
            }
        );


    }) 





    jQuery('.msg-customer').on('click', function(e){

        var user_id = jQuery(this).data('userid');        
                  
        swal({
            title: "Send Message",
            text: "Enter your message below",
            type: "input",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Send",
            cancelButtonText: "Cancel",
            inputPlaceholder: "Write something",
            closeOnConfirm: false,
            closeOnCancel: true
            },
            function(inputValue){
                if (inputValue === false) return false;

                if (inputValue === "") {
                    swal.showInputError("Please write something!");
                    return false
                }

                //send message through AJAX
                var post_data = {'action':'messagecustomer','user_id' : user_id, 'content' : inputValue};
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    timeout : 10000,
                    crossDomain:true,
                    xhrFields: {withCredentials: true},
                    data: post_data,
                    success: function (data, status)
                    {
                            
                        try{
                            var data_obj = JSON.parse(data);
                        }catch(e){
            
                            imgurl = '../img/info_.gif?a=' + Math.random();
    
                            swal({
                                        title: '<h1>Error</h1>',
                                        text: 'Failed to send message!',
                                        imageUrl:imgurl,
                                        html:true
                            });

                        }
            
                        
                        if(data_obj.hasOwnProperty('error')){
                            imgurl = '../img/info_.gif?a=' + Math.random();
    
                            swal({
                                        title: '<h1>Error</h1>',
                                        text: 'Failed to send message!',
                                        imageUrl:imgurl,
                                        html:true
                            });
                        }
                        
                        
                        if(data_obj.hasOwnProperty('success')){

                            imgurl = '../img/success_.gif?a=' + Math.random();
    
                            swal({
                                        title: '<h1>Success</h1>',
                                        text: data_obj.success,
                                        imageUrl:imgurl,
                                        html:true
                            });
                            
                            
                        } 
                        
                        

                        
            
            
                    },
                    error: function(jqXHR,textStatus, errorThrown) {  
                        
                        imgurl = '../img/info_.gif?a=' + Math.random();
    
                        swal({
                                    title: '<h1>Error</h1>',
                                    text: 'Failed to send message!',
                                    imageUrl:imgurl,
                                    html:true
                        });
                        
                    }
                    
                });

                
             
            }
        );


    }) 




   




     jQuery('#busy').modal({
        keyboard: false,
        backdrop:1,
        show:0
    })

    jQuery('#busy').on('shown.bs.modal', function() {
        jQuery("body.modal-open").removeAttr("style");
    });

         


       

            var staffsrc = $('#staffimage').val();
             if( $('#staffimage').val() != ''){
                   
                   $('#staffpic').attr('src', staffsrc).show();
               } 

            // Executes a callback detecting changes with a frequency of 1 second
            $("#staffimage").observe_field(1, function( ) {
                var default_img = '../../img/staff.jpg';  
            
                $('#staffpic').attr('src',this.value).show();
            
                  // $('#staffpic').attr('src', default_img).show();
            if( $('#staffimage').val() == ''){
                   
                   $('#staffpic').attr('src', default_img).show();
               }  

             });
       
        

         

  




        jQuery('.delete-item').on("click", function(e){
            e.preventDefault();
            var element = jQuery(this);
            var del_msg = jQuery(this).data('msg');

            if(del_msg == null)del_msg = "This item will be deleted from the database";
             
                swal({
                    title: "Are you sure?",
                    text: del_msg ,
                    imageUrl: "../img/info_.gif?a=" + Math.random(),
                    html:true,
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "No, cancel!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                    },
                    function(isConfirm){
                    if (isConfirm) {
                         var link = jQuery(element).attr('href');
                         window.location = link;
                   } 
                });

        

        });



        jQuery('.confirm-action').on("click", function(e){
            e.preventDefault();
            var element = jQuery(this);
            var confirm_msg = jQuery(this).data('msg');
            var action_url = jQuery(this).data('url');

            if(confirm_msg == null)confirm_msg = "This action needs your confirmation";
             
                swal({
                    title: "Are you sure?",
                    text: confirm_msg ,
                    imageUrl: "../img/info_.gif?a=" + Math.random(),
                    html:true,
                    showCancelButton: true,
                    confirmButtonColor: "#3e73f9",
                    confirmButtonText: "Continue",
                    cancelButtonText: "Cancel!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                    },
                    function(isConfirm){
                    if (isConfirm) {                         
                         window.location = action_url;
                   } 
                });

        

        });
        

       

 

        var x ;
        jQuery('.staff-list').fancybox({	
            'width'		: 700,
            'height'	: 600,
            'type'		: 'iframe',
                'autoScale'    	: true,
                beforeClose : function(){

                    x = "";
                    
                    $('.fancybox-iframe').contents().find('input:checked').each(function() {
                        x = x + $(this).attr("data-id")  + ",";
                        
                    });

                  if(x != ""){
                    $('#course-staff').val(x);
                  }
                },
               
                closeBtn: 0
            });

            
            jQuery('.staff-list-post').fancybox({	
            'width'		: 700,
            'height'	: 600,
            'type'		: 'iframe',
                'autoScale'    	: true,
                beforeClose : function(){

                    x = "";
                    y = '';
                    
                    $('.fancybox-iframe').contents().find('input:checked').each(function() {
                        x = $(this).attr("data-drvrid");
                        y = $(this).attr("data-drvrname");                        
                    });

                    alert(y + " | " + x);

                  /* if(x != ""){
                    $('#post-staff').val(x);
                  } */
                },
               
                closeBtn: 0
            });


            var imgurl = '';
            var op_result;
            jQuery('.user-qualification').fancybox({	
            'width'		: 700,
            'height'	: 600,
            'type'		: 'iframe',
                'autoScale'    	: true,
                closeBtn: 1,
                beforeClose : function(){

                 op_result =  $('.fancybox-iframe').contents().find('#result').attr('value');
                 if(op_result == 'success'){  
                    imgurl = '../img/success_.gif?a=' + Math.random();
                    
                    swal({
                                title: '<h1>Success</h1>',
                                text: 'User Qualification verification update complete!',
                                imageUrl:imgurl,
                                html:true
                            });
                    
                }
                if(op_result == 'error'){
                        imgurl = '../img/info_.gif?a=' + Math.random();
                    
                        swal({
                                    title: '<h1>Error</h1>',
                                    text: 'User Qualification verification update Failed!',
                                    imageUrl:imgurl,
                                    html:true
                        });


                }},
            });


           
            jQuery('.transact-customer').fancybox({	
            'width'		: 800,
            'height'	: 400,
            'type'		: 'iframe',
                'autoScale'    	: true,
                closeBtn: 1
                
            });


            


            jQuery('.drvr-location').fancybox({	
                'width'		: 800,
                'height'	: 500,
                'type'		: 'inline',
                beforeClose : function(){
                    clearInterval(location_update_timer_id);
                },
                closeBtn: 1
                    
            });



            /* jQuery('.dispatch-driver-list').fancybox({	
                'width'		: 700,
                'height'	: 600,
                'type'		: 'iframe',
                    'autoScale'    	: true,
                    closeBtn: 1
                    
            }); */

            jQuery('.dispatch-driver-list').on('click', function(){
                var row_data = jQuery(this);
                var row_data_id = row_data.data('rowid');
                var row_book_id = row_data.data('bookid');
                var row_data_href = row_data.data('href');
                var row_driver_assigned_td_selector = '#driver-assigned-' + row_data_id;
                //jQuery(row_driver_assigned_td_selector).html('micheal');
                
                jQuery.fancybox([{
                    href : row_data_href,	
                    width		: 700,
                    height	: 500,
                    type		: 'iframe',
                    fitToView : false,
                    autoSize : false,
                    
                        beforeClose : function(){

                            var x = "";
                            var y = "";

                            var selected = $('.fancybox-iframe').contents().find('#okclicked').val();
                            if(selected == '0'){
                                return;
                            }

                            $('.fancybox-iframe').contents().find('input:checked').each(function() {
                                x = $(this).attr("data-drvrid");
                                y = $(this).attr("data-drvrname");                        
                            });
                                    
        
                          if(x != "" && y != ""){
                            
                            //**************Assign driver via AJAx******************
                                var post_data = {'action':'bookingassigndriver','driver_id' : x,'booking_id':row_book_id};
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    timeout : 10000,
                                    crossDomain:true,
                                    xhrFields: {withCredentials: true},
                                    data: post_data,
                                    success: function (data, status)
                                    {
                                        console.log(data);
                                        var data_obj = undefined;    
                                        try{
                                            data_obj = JSON.parse(data);
                                        }catch(e){
                            
                                            imgurl = '../img/info_.gif?a=' + Math.random();
                    
                                            swal({
                                                        title: '<h1>Error</h1>',
                                                        text: 'Failed to update record!',
                                                        imageUrl:imgurl,
                                                        html:true
                                            });

                                        }
                            
                                        
                                        if(data_obj.hasOwnProperty('error')){
                                            imgurl = '../img/info_.gif?a=' + Math.random();
                    
                                            swal({
                                                        title: '<h1>Error</h1>',
                                                        text: data_obj.error,
                                                        imageUrl:imgurl,
                                                        html:true
                                            });
                                        }
                                        
                                        
                                        if(data_obj.hasOwnProperty('success')){

                                            jQuery(row_driver_assigned_td_selector).html(y);

                                            imgurl = '../img/success_.gif?a=' + Math.random();
                    
                                            swal({
                                                        title: '<h1>Success</h1>',
                                                        text: data_obj.success,
                                                        imageUrl:imgurl,
                                                        html:true
                                            });
                                            
                                        } 
                                        
                                        

                                        
                            
                            
                                    },
                                    error: function(jqXHR,textStatus, errorThrown) {  
                                        
                                        imgurl = '../img/info_.gif?a=' + Math.random();
                    
                                        swal({
                                                    title: '<h1>Error</h1>',
                                                    text: 'Failed to update record!',
                                                    imageUrl:imgurl,
                                                    html:true
                                        });
                                        
                                    }
                                    
                                });





                            //**************************** */

                          }
                          
                        }
                        
                        
                }]);

            })


            jQuery('.view_cert_sample').fancybox({	
                'width'		: 1200,
                'height'	: 600,
                'type'		: 'iframe',
                    'autoScale'    	: true,
                    'wrapCSS' : 'cert-preview-frame',
                    'afterLoad' : function(){
                                        jQuery('.cert-preview-frame .fancybox-iframe').contents().find("head").append(jQuery("<style type='text/css'>  img{width:100%;}  </style>"));
                                    },
                    closeBtn: 1
                    
                });


           

                tinymce.init({
                    extended_valid_elements : "a[class|name|href|target|title|onclick|rel],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],span[class|name|title|onclick]",
                    selector: '.textformat',
                    height: 200,
                    menubar: false,
                    relative_urls : false,
                    remove_script_host : false,
                    convert_urls : true,
                    code_dialog_height: 200,
                    setup: function(editor) {
                    editor.on('change', function () {
                        editor.save();
                    });},
                    plugins: [
                        'advlist autolink lists link image charmap print preview anchor',
                        'searchreplace visualblocks code fullscreen',
                        'insertdatetime media table contextmenu paste code'
                    ],
                    toolbar: 'undo redo | insert | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | code | image'
                    
                    /* external_filemanager_path:"/admin/filemanager/",
                    filemanager_title:"KSmart Filemanager" ,
                    external_plugins: { "filemanager" : "/admin/filemanager/plugin.min.js"} */
        
                               
                    });


            
            
        
       



  } );



   function readURL(input) {
            if (input.files && input.files[0]) {
				var imgPath = input.files[0].name;
				var extn = imgPath.substring(imgPath.lastIndexOf('.') + 1).toLowerCase();
			    if (extn == "gif" || extn == "png" || extn == "jpg" || extn == "jpeg") {
       			if (typeof (FileReader) != "undefined") {
                var reader = new FileReader();			
                reader.onload = function (e) {
                    jQuery('#passport')
                        .attr('src', e.target.result)
                        .width(150)
                        .height('auto');						
						
                };

                reader.readAsDataURL(input.files[0]);
            }
        }
			}
        }
        

        function readCertTplURL(input) {
            if (input.files && input.files[0]) {
				var imgPath = input.files[0].name;
				var extn = imgPath.substring(imgPath.lastIndexOf('.') + 1).toLowerCase();
			    if (extn == "gif" || extn == "png" || extn == "jpg" || extn == "jpeg") {
       			if (typeof (FileReader) != "undefined") {
                var reader = new FileReader();			
                reader.onload = function (e) {
                    jQuery('#certtemplate')
                        .attr('src', e.target.result)
                        .width(300)
                        .height('auto');						
						
                };

                reader.readAsDataURL(input.files[0]);
            }
        }
			}
		}    







$('#gen-pass').click(function(){

    var password = generatePass(10);
    $('#password').val(password);

})



function generatePass(password_len){

    var smallalpha="abcdefghijklmnopqrstuvwxyz";
    var capalpha = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    var numeric="123456789";
    var symbols="!@#_&+-";
    var p_chars='';
    var temp = '';
    var smallalpha_len = Math.floor(password_len/2);
    var capsalpha_len = 1;
    var symbols_len = 1;
    var numeric_len = password_len - smallalpha_len - capsalpha_len - symbols_len;
   

    for (i=0;i<capsalpha_len;i++)
        temp+=capalpha.charAt(Math.floor(Math.random()*capalpha.length));

    for (i=0;i<smallalpha_len;i++)
        temp+=smallalpha.charAt(Math.floor(Math.random()*smallalpha.length));

    for (i=0;i<symbols_len;i++)
        temp+=symbols.charAt(Math.floor(Math.random()*symbols.length));

    for (i=0;i<numeric_len;i++)
        temp+=numeric.charAt(Math.floor(Math.random()*numeric.length));    

        temp=temp.split('').sort(function(){return 0.5-Math.random()}).join('');

    return temp;
}





//*****************image read function *************************

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
                .width('auto')
                .height(100);

                           
                
        };

        reader.readAsDataURL(input.files[0]);
    }
}
    }
}


function readImgFile(input, callback) {
    if (input.target.files && input.target.files[0]) {
        var imgPath = input.target.files[0].name;
        var imgSize = input.target.files[0].size;
        
        var extn = imgPath.substring(imgPath.lastIndexOf('.') + 1).toLowerCase();
        var result = {data:'',error:1,error_msg:''};
        if(imgSize > 204800){
            //filesize greater than 200KB
            result.error_msg = 'File size must not be greater than 200KB';
            callback(result);
            return;
        }

        if (extn == "jpg" || extn == "jpeg") {
           if (typeof (FileReader) != "undefined") {
                var reader = new FileReader();			
                reader.onload = function (e) {
                    /* jQuery('#passport')
                        .attr('src', e.target.result)
                        .width(150)
                        .height('auto'); */
                    
                    
                    
                    result.data = e.target.result;
                    result.error = 0;
                    callback(result);
                                
                        
                };

                reader.readAsDataURL(input.target.files[0]);
            }

        }else{
            result.error_msg = 'Invalid file type. Only JPG files are allowed.';
            callback(result);
        }
    }
}












 

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




