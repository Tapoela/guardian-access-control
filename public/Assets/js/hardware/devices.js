$(function () {

    $('#deviceTable').DataTable({

        responsive: true,

        autoWidth: false,

        pageLength: 10,

        order: [[0, 'asc']],

        language: {
            emptyTable: "No hardware devices configured."
        }

    });

});