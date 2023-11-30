(function($) {
    'use strict';
    $(window).load(function() {

        var loadAllRecords = function(wpsfSearch = '') {
            $('#wpsf-show-data').append(`
                <table id="list-users" class="table table-striped">
            	  <thead>
            	    <tr>
            	      <th scope="col">ID</th>
            	      <th scope="col">Full name</th>
            	      <th scope="col">Username</th>
            	      <th scope="col">Email</th>
            	    </tr>
            	  </thead>
            	  <tbody>
            	  </tbody>
            	</table>
            	    `);
            const table_obj = $('#list-users'),
                table_tbody = table_obj.find('tbody');
            let urlApi = wpsf_vars.api_url + '/simpleform/v1/list_data';

            if (wpsfSearch.length > 0) {
                urlApi += "?search=" + wpsfSearch;
            }

            $.ajax({
                type: 'GET',
                url: urlApi,
                dataType: 'json',
                data: {},
                beforeSend() {},
                success: function(response) {
                    table_tbody.html('');
                    let output;
                    $.each(response, function(key, value) {
                        output += `
                <tr>
                <th ><a href="javascript:void(0);" class="user-detail" data-user-id="${value.id}" >${value.id}</a></th>
                <td ><a href="javascript:void(0);" class="user-detail" data-user-id="${value.id}" >${value.fullname}</a></td>
                <td ><a href="javascript:void(0);" class="user-detail" data-user-id="${value.id}" >${value.username}</a></td>
                <td ><a href="javascript:void(0);" class="user-detail" data-user-id="${value.id}" >${value.email}</a></td>
                </tr>
                `;
                    });
                    table_tbody.html(output);

                    //attach event
                    $('.user-detail').on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        $('#myModal')
                            .find('.modal-body')
                            .html('<i class="fa fa-sync"></i>');
                        $('#myModal').modal('show');

                        const userId = $(this).data('user-id');

                        $.ajax({
                            type: 'GET',
                            url: wpsf_vars.api_url + '/simpleform/v1/list_data/' + userId,
                            dataType: 'json',
                            data: {},
                            beforeSend() {},
                            success: ResUserDetail,
                        });
                    });
                },
            });




        };




        const ResUserDetail = function(response) {
            $('#myModal')
                .find('.modal-body')
                .html('');

            const {
                __
            } = wp.i18n;

            let user = response,
                $output;

            $output = `
                    <div class="row">
                        <div class="col-md-4">
                            <label>${__('Full Name', 'sfdc-plugin')}</label>
                        </div>
                        <div class="col-md-8">
                            ${user.fullname}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label>${__('Username', 'sfdc-plugin')}</label>
                        </div>
                        <div class="col-md-8">
                            ${user.username}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label>${__('Email', 'sfdc-plugin')}</label>
                        </div>
                        <div class="col-md-8">
                            ${user.email}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label>${__('Address', 'sfdc-plugin')}</label>
                        </div>
                        <div class="col-md-8">
                            ${user.address}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label>${__('Phone', 'sfdc-plugin')}</label>
                        </div>
                        <div class="col-md-8">
                            ${user.phone}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label>${__('Website', 'sfdc-plugin')}</label>
                        </div>
                        <div class="col-md-8">
                            ${user.website}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label>${__('Company', 'sfdc-plugin')}</label>
                        </div>
                        <div class="col-md-8">
                            ${user.company}
                        </div>
                    </div>
                   
                    `;

            $('#myModal')
                .find('.modal-body')
                .html($output);
        };



        //create record
        $("#wpsf-add-new-form").submit(function(e) {
            e.stopPropagation();
            e.preventDefault(); // avoid to execute the actual submit of the form.

            var form = $(this);

            $.ajax({
                type: "POST",
                url: wpsf_vars.api_url + '/simpleform/v1/insert_data/',
                data: form.serialize(), // serializes the form's elements.
                success: function(data) {
                    //clearing form
                    $(':input', form)
                        .not(':button, :submit, :reset, :hidden')
                        .val('');
                    $('#wpsf-show-data').html('');

                    //loading records
                    loadAllRecords();
                }
            });

        });

        //load all records
        loadAllRecords();


        //search record
        $('#wpsf-search-addon').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            let $searchVal = $('#wpsf-search-content').val();
            $('#wpsf-show-data').html('');
            loadAllRecords($searchVal);

        });

    });
})(jQuery);