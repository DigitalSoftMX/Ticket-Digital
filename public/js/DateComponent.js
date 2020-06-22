 function now() {
     var f = new Date(); //obteniendo fecha
     var day = f.getDate();
     var month = (f.getMonth() + 1);
     var year = f.getFullYear();
     if ((f.getMonth() + 1) < 10) {
         month = '0' + (f.getMonth() + 1);
     }
     if (f.getDate() < 10) {
         day = '0' + (f.getDate());
     }
     return year + '-' + month + '-' + day;
 }


 function manana() {
     var f = new Date(); //obteniendo fecha
     var day = f.getDate()+1;
     var month = (f.getMonth() + 1);
     var year = f.getFullYear();
     if ((f.getMonth() + 1) < 10) {
         month = '0' + (f.getMonth() + 1);
     }
     if (f.getDate() < 10) {
         day = '0' + (f.getDate());
     }
     return year + '-' + month + '-' + day;
 }


 var last_day = new Date((now()).substr(0, 4), (now()).substr(5, 2), 0);
 day_compare = (now()).substr(0, 4) + '-' + (now()).substr(5, 2) + '-' + last_day.getDate();

 function compare_days() {
    anio = (now()).substr(0, 4);
         mes = (now()).substr(5, 2);
         dia = (now()).substr(8,2);
     if ($('#calendar_first').val() == day_compare) {
         if (mes == 12) {
             mes = '01';
             dia = '01';
             anio++;
             return (anio + '-' + mes + '-' + dia);
         } else {
             mes++;
             if (mes < 10) {
                 mes = '0' + (mes);
             }
             dia = '01';
             return (anio + '-' + mes + '-' + dia)
         }
     } else {
         date = parseInt(($('#calendar_first').val()).substr(8, 2));
         date++;
         if (date < 10) {
             date = '0' + (date);
         }
         return (anio + '-' + mes + '-' + date);
     }
 }

 function init_calendar(id, min_date, max_date) {
     $('#' + id).datetimepicker({

        format: 'YYYY-MM-DD',
        minDate: min_date,
        maxDate: max_date,
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-chevron-up",
            down: "fa fa-chevron-down",
            previous: 'fa fa-chevron-left',
            next: 'fa fa-chevron-right',
            today: 'fa fa-screenshot',
            clear: 'fa fa-trash',
            close: 'fa fa-remove'
        },
        daysOfWeekDisabled:[0],
     });
 }

 function destroy_calendar(id) {
     $('#' + id).data("DateTimePicker").destroy();
 }


 function iniciar_selector_de_archivos()
{
    // FileInput
  $('.form-file-simple .inputFileVisible').click(function() {
    $(this).siblings('.inputFileHidden').trigger('click');
  });

  $('.form-file-simple .inputFileHidden').change(function() {
    var filename = $(this).val().replace(/C:\\fakepath\\/i, '');
    $(this).siblings('.inputFileVisible').val(filename);
  });

  $('.form-file-multiple .inputFileVisible, .form-file-multiple .input-group-btn').click(function() {
    $(this).parent().parent().find('.inputFileHidden').trigger('click');
    $(this).parent().parent().addClass('is-focused');
  });

  $('.form-file-multiple .inputFileHidden').change(function() {
    var names = '';
    for (var i = 0; i < $(this).get(0).files.length; ++i) {
      if (i < $(this).get(0).files.length - 1) {
        names += $(this).get(0).files.item(i).name + ',';
      } else {
        names += $(this).get(0).files.item(i).name;
      }
    }
    $(this).siblings('.input-group').find('.inputFileVisible').val(names);
  });

  $('.form-file-multiple .btn').on('focus', function() {
    $(this).parent().siblings().trigger('focus');
  });

  $('.form-file-multiple .btn').on('focusout', function() {
    $(this).parent().siblings().trigger('focusout');
  });
}
