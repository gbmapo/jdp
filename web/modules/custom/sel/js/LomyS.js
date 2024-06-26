jQuery(document).ready(function ($) {

  $('#listofmyservices').DataTable({

    info: false,
    language: {
       search: "_INPUT_",
       searchPlaceholder: "Rechercher...",
    },
    ordering: false,
    paging: false,
    fixedHeader: true,
    responsive: true,
    columnDefs: [
      {targets: [0,4,5,6,7,8,9,10], className: 'dt-head-center'},
      {responsivePriority: 1, targets: 0},
      {responsivePriority: 1, targets: 2},
      {responsivePriority: 1, targets: 3},
      {responsivePriority: 2, targets: 10},
      {responsivePriority: 2, targets: 11},
      {responsivePriority: 3, targets: 5},
      {responsivePriority: 3, targets: 6},
      {responsivePriority: 3, targets: 7},
      {responsivePriority: 4, targets: 4},
      {responsivePriority: 5, targets: 9},
      {responsivePriority: 6, targets: 8},
      {responsivePriority: 10001, targets: 1},
    ],
    initComplete: function () {
//    this.api().columns([1, 10]).every(function (i) {
      this.api().columns([10]).every(function (i) {
        var column = this;
        var select = $('<select><option value=""></option></select>')
          .appendTo($(column.header()))
          .on('change', function () {
            var val = $.fn.dataTable.util.escapeRegex(
              $(this).val()
            );
            column
              .search(val ? '^' + val + '$' : '', true, false)
              .draw();
          });

        switch (i) {
          case 10:
            column.data().unique().sort().each(function (d, j) {
              select.append('<option value="' + d + '">' + d + '</option>');
            });
            break;
          default:
        }
      });
    }

  });

});
