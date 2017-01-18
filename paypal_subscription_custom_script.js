jQuery(document).ready( function() {
    //jQuery('#empty_plans').delay(3000).slideUp(1000);
   jQuery('.create_plan_form').hide();   
   jQuery('body').on("click","#plan_create_enable",function(){
      if(jQuery(this).val()==0){
         jQuery(this).val(1);
         jQuery('.create_plan_form').show();
      }
      else{
         jQuery('.create_plan_form').hide();
         jQuery(this).val(0);
      }
   });
   jQuery('body').on('click','#create_plan',function(){
      jQuery('#create_plan_spinner').css({"visibility":"visible"});
      jQuery(this).hide();
      var plan_name=jQuery('#plan_name').val();
      var plan_description=jQuery('#plan_description').val();
      var frequency=jQuery('#frequency').val();
      var frequency_interval=jQuery('#frequency_interval').val();
      var cycles=jQuery('#cycles').val();
      var price=jQuery('#price').val();
      var setup_price=jQuery('#setup_price').val();
      var shipping_fee=jQuery('#shipping_fee').val();
      var plan_tax=jQuery('#plan_tax').val();
      jQuery.ajax({
         type : "post",
         dataType : "json",
         url : myAjax.ajaxurl,
         data : {action:"create_paypal_subscription_plan",'shipping_fee':shipping_fee,'plan_tax':plan_tax,'plan_name':plan_name,'plan_description':plan_description,'frequency':frequency,'cycles':cycles,'price':price,'setup_price':setup_price,'frequency_interval':frequency_interval},
         success: function(response) {
            jQuery('#create_plan').show();
            jQuery('#create_plan_spinner').css({"visibility":"hidden"});
            if(response.action == "success") {
              jQuery('#empty_plans').slideUp(1000);
              jQuery('.add_plan').append(response.data);
              jQuery('.load_more_plans').show();
            }
            else {
               alert("Your plan could not be created");
            }
         }
      });
   });
   jQuery('body').on('click','#load_plan',function(){
      jQuery(this).parents('p').find('#load_more_plan_spinner').css({"visibility":"visible"});
      jQuery(this).hide();
      jQuery('#save_plan').hide();
      var page=jQuery('#load_plan').attr('page-data');
      var post_id=jQuery('#load_plan').attr('post-id');
      jQuery.ajax({
         type : "post",
         dataType : "json",
         url : myAjax.ajaxurl,
         data : {action:"load_paypal_subscription_plan",'page':page,'post_id':post_id},
         success: function(response) {
            if(response.action == "success") {
               jQuery('#load_more_plan_spinner').css({"visibility":"hidden"});
               jQuery('#load_plan').show();
               jQuery('#save_plan').show();
               jQuery('.add_plan').append(response.data);
               page++;
               jQuery('#load_plan').attr("page-data",page);               
            }
            else {
                jQuery('#load_more_plan_spinner').css({"visibility":"hidden"});
                jQuery('.load_more_plans').append(response.data);
                jQuery('#save_plan').show();
                jQuery('#no_more_plans').delay(2000).slideUp(1000);
            }
         }
      });
   });
  jQuery('body').on('click','#plan',function(){
    var plan_id=jQuery(this).attr('data-value');
    jQuery('#plan_id').val(plan_id);
  });
  jQuery('body').on('click','#save_plan',function(){
    jQuery(this).parents('p').find('#load_more_plan_spinner').css({"visibility":"visible"});
    jQuery(this).hide();
    jQuery('#load_plan').hide();
    jQuery('#load_plan').hide();    
    var post_id=jQuery('#load_plan').attr('post-id');
    var plan_id=jQuery('#plan_id').val();
    jQuery.ajax({
      type : "post",
      dataType : "json",
      url : myAjax.ajaxurl,
      data : {action:"save_paypal_subscription_plan",'plan_id':plan_id,'post_id':post_id},
      success: function(response) {
        if(response.action == "success") {
          jQuery('#load_more_plan_spinner').css({"visibility":"hidden"});
          jQuery('#_regular_price').val(response.data);
          jQuery('#_sale_price').val(response.data);
          jQuery('#_virtual').attr("checked");
          jQuery('#load_plan').show();
          jQuery('#save_plan').show();
        }
        else {
          jQuery('#load_more_plan_spinner').css({"visibility":"hidden"});
          jQuery('.load_more_plans').append(response.data);
          jQuery('.save_plan_error').delay(1000).slideUp(1000);
          jQuery('#load_plan').show();
          jQuery('#save_plan').show();
        }
      }
    });
  });
   jQuery('body').on('keydown', '#frequency_interval', function(e){
    var verified = (e.which == 8 || e.which == undefined || e.which == 0) ? null : String.fromCharCode(e.which).match(/[^0-9]/);
    if (verified) {e.preventDefault();}
    //-1!==$.inArray(e.keyCode,[46,8,9,27,13,110,190])||/65|67|86|88/.test(e.keyCode)&&(!0===e.ctrlKey||!0===e.metaKey)||35<=e.keyCode&&40>=e.keyCode||(e.shiftKey||48>e.keyCode||57<e.keyCode)&&(96>e.keyCode||105<e.keyCode)&&e.preventDefault()
  });
  jQuery('body').on('keydown', '#cycles', function(e){
    var verified = (e.which == 8 || e.which == undefined || e.which == 0) ? null : String.fromCharCode(e.which).match(/[^0-9]/);
    if (verified) {e.preventDefault();}
    //-1!==$.inArray(e.keyCode,[46,8,9,27,13,110,190])||/65|67|86|88/.test(e.keyCode)&&(!0===e.ctrlKey||!0===e.metaKey)||35<=e.keyCode&&40>=e.keyCode||(e.shiftKey||48>e.keyCode||57<e.keyCode)&&(96>e.keyCode||105<e.keyCode)&&e.preventDefault()
  });  
});