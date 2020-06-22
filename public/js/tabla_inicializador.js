function iniciar_date(nombre_tabla)
{
  table=$('#'+nombre_tabla).DataTable({

        responsive: true,
        dom: 'Blfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                className: 'btn btn-sm btn-success pull-left',
                exportOptions: {
                    columns: ':visible'
                }
            }
        ],
        'pageLength': 10,
        'lengthMenu': [[10, 20, 25, 50, -1], [10, 20, 25, 50, 'Todos']],
        language: {
          search: "_INPUT_",
          searchPlaceholder: "",
          processing:     "Transformación...",
          search:         "Buscar:",
          lengthMenu:     "Mostrar _MENU_ Resultados",
          info:           "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
          infoEmpty:      "0 Entradas de 0 Disponibles",
          infoFiltered:   "(filtrado de entre _MAX_ elementos disponibles)",
          infoPostFix:    "",
          loadingRecords: "Cargando...",
          zeroRecords:    "No se encontraron elementos coincidentes.",
          emptyTable:     "No hay datos disponibles.",
          paginate: {
            first:      "<<",
            previous:   "<",
            next:       ">",
            last:       ">>"
          },
          aria: {
            sortAscending : "active para ordenar la columna en orden ascendente",
            sortDescending: "active para ordenar la columna en orden descendente"
          }
        }
        
      });
}