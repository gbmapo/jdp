jQuery(document).ready(function ($) {

//  $.fn.dataTable.moment('MM/YYYY');

  $('#listofpersons').DataTable({
    "language": {
      "sProcessing": "Traitement en cours...",
//    "sSearch": "Rechercher&nbsp;:",
       search: "_INPUT_",
       searchPlaceholder: "Rechercher...",
      "sLengthMenu": "Afficher _MENU_ &eacute;l&eacute;ments",
      "sInfo": "Affichage de _TOTAL_ &eacute;l&eacute;ments",
      "sInfoEmpty": "Aucun &eacute;l&eacute;ment &agrave; afficher",
      "sInfoFiltered": "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
      "sInfoPostFix": "",
      "sLoadingRecords": "Chargement en cours...",
      "sZeroRecords": "Aucun &eacute;l&eacute;ment &agrave; afficher",
      "sEmptyTable": "Aucune donn&eacute;e disponible dans le tableau",
      "oPaginate": {
        "sFirst": "Premier",
        "sPrevious": "Pr&eacute;c&eacute;dent",
        "sNext": "Suivant",
        "sLast": "Dernier"
      },
      "oAria": {
        "sSortAscending": ": activer pour trier la colonne par ordre croissant",
        "sSortDescending": ": activer pour trier la colonne par ordre d&eacute;croissant"
      }
    },
    "info": true,
    "order": [],
    "paging": false,
    "columnDefs": [{
      "targets": [3, 4, 9, 10],
      "orderable": false
    }],
    orderCellsTop: true,
    "dom": '<"top"if>',
    /* Results in:
        <div class="top">
          {information}
          {filter}
        </div>
    */
    fixedHeader: true,
    initComplete: function () {
      this.api().columns([5, 6]).every(function (i) {
        var column = this;
        var select = $('<select><option value=""></option></select>')
          .appendTo($("#listofpersons thead tr:eq(1) th").eq(column.index()).empty())
          .on('change', function () {
            var val = $.fn.dataTable.util.escapeRegex(
              $(this).val()
            );
            column
              .search(val ? '^' + val + '$' : '', true, false)
              .draw();
          });

        switch (i) {
          case 5:
          case 6:
            column.data().unique().sort().each(function (d, j) {
              select.append('<option value="' + d + '">' + d + '</option>')
            });
            break;
          default:
        }

      });
    }
  });

});
