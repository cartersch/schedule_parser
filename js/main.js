$(function(){
   var Parser = Parser || {};

   Parser.init = function(){
       this.howToAction();
       this.submitAction();
   };

   Parser.stickyMenu = false;

   Parser.setSpinner = function(){
       var $results = $('#results');
       var resultsSpin = '<div class="spin"><span class="glyphicon glyphicon-asterisk"></span></div>';
       var uploadSpin = '<div class="spin small"><span class="glyphicon glyphicon-asterisk"></span></div>';

       if(this.stickyMenu){
           $('.spin-placeholder').after(uploadSpin);
       } else{
           $results.empty();
           $results.html(resultsSpin);
       }

   }

   Parser.dayCounter = 0;

   Parser.submitAction = function(){
       var $results = $('#results');
       $('#uploadForm').submit(function(e){
           e.preventDefault();
           Parser.setSpinner();
           var postAction = $(this).attr('action');
           var formData = new FormData(document.getElementById('uploadForm'));

           $.ajax({
             url: postAction,
             type: "POST",
             data: formData,
             processData: false,  // tell jQuery not to process the data
             contentType: false,   // tell jQuery not to set contentType
             success : function(data){
                 Parser.changeButtonText();
                 Parser.stickyUpload();
                 Parser.buildTable(data);
             },
             error : function(){
                 $results.empty();
                 $results.html('<div class="alert alert-danger">Something bad happened...</div>');
             }
           });
       });
   };

   Parser.printAction = function(){
       $('.print-button').click(function(){
           window.print();
       });
   }

   Parser.stickyUpload = function(){

       $(window).scroll(function(e){
           $el = $('#upload');
           if($(this).scrollTop() > 250){
               Parser.stickyMenu = true;
               $el.addClass('sticky');
           } else {
               Parser.stickyMenu = false;
               $el.removeClass('sticky');
           }
       })
   }

   Parser.changeButtonText = function(){
       $('button[type="submit"]').html('Refresh <i class="glyphicon glyphicon-refresh"></i>');
   };

   Parser.buildTable = function(data){
       var obj = {scheduleItems : $.parseJSON(data)};
       this.dayCounter = 0;
       this.printAction();
       console.log(obj);
       var template = Handlebars.compile($('#schedule-table').html());
       var table = template(obj);
       $('#upload .spin').remove();
       $('.print-button').show();
       $('#results').empty().html(table);
   };

   Parser.howToAction = function(){
       $('.how-to-button').click(function(e){
          e.preventDefault();
          var id = $(this).attr('href');
          $('.container').addClass('has-overlay');
          $(id).addClass('overlay');

       });

       $('.close-button').click(function(){
         $('.container').removeClass('has-overlay');
         $('#how-to').removeClass('overlay');
       });
   };

   Handlebars.registerHelper('getDay', function(){
      Parser.dayCounter++;
      return Parser.dayCounter;
   });

   Handlebars.registerHelper('displayEndTime', function(show, options){
       if(show == 'Yes'){
           return options.fn(this);
       } else
           return options.inverse(this);

   });

   Handlebars.registerHelper('isSpeaker', function(type, options){
       if(type == 'Lecture'){
           return options.fn(this);
       } else {
           return options.inverse(this);
       }
   });

   Handlebars.registerHelper('formatDate', function(date){
       var dateParts = date.split(' ');
       var dateString = dateParts[0] + 'T' + dateParts[1] + 'Z';
       var dateString = Date.parse(dateString);
       var useDate = dateString + 14400;
       
       var dateObj = new Date(useDate);
       var hours = dateObj.getUTCHours();
       var minutes = dateObj.getMinutes();
       var ampm = hours >= 12 ? 'pm' : 'am';
       hours = hours % 12;
       hours = hours ? hours : 12; // the hour '0' should be '12'
       minutes = minutes < 10 ? '0'+minutes : minutes;
       var strTime = hours + ':' + minutes + ' ' + ampm;
       return strTime;
   });



   Parser.init();
});
