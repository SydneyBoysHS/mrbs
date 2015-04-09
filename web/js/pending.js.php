<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

// =================================================================================

// Extend the init() function 
?>
var oldInitPending = init;
init = function(args) {
  oldInitPending.apply(this, [args]);

  <?php
  // Turn the table into a datatable, with subtables that appear/disappear when
  // the control is clicked, with the subtables also being datatables.  Note though
  // that the main and sub-datatables are independent and we only display the main search
  // box which just applies to the main table rows.  (I suppose it would be possible to do
  // something clever with the main search box and get it to search the subtables as well)
  //
  // The datatables with subtables don't seem to work properly in IE7, so don't
  // bother with them if we're using IE7
  ?>
  if (lteIE7)
  {
    $('.js div.datatable_container').css('visibility', 'visible');
  }
  else
  {
    var maintable = $('#pending_table'),
        subtables;
        
    <?php
    // Add a '-' control to the subtables and make them close on clicking it
    ?>
    maintable.find('table.sub th.control')
             .text('-');
   
    
    $(document).on('click', 'table.sub th.control', function () {
        var nTr = $(this).closest('.table_container').parent().prev(),
            serial = $(this).parent().parent().parent().attr('id').replace('subtable_', '');
            
        $('#subtable_' + serial + '_wrapper').slideUp( function () {
            pendingTable.row(nTr).child.hide();
            nTr.show();
          });
      });
      
    <?php
    // Detach all the subtables from the DOM (detach keeps a copy) so that they
    // don't appear, but so that we've got the data when we want to "open" a row
    ?>
    subtables = maintable.find('tr.sub_table').detach();
    
    <?php
    // Set up a click event that "opens" the table row and inserts the subtable
    ?>
    maintable.find('td.control')
             .text('+');
             
    $(document).on('click', 'td.control', function () {
        
        var nTr = $(this).parent(),
            serial = nTr.attr('id').replace('row_', ''),
            subtableId = 'subtable_' + serial,
            subtable = subtables.find('#' + subtableId).parent().clone(),
            columns = [],
            subDataTable;
            
        <?php
        // We want the columns in the main and sub tables to align.  So
        // find the widths of the main table columns and use those values
        // to set the widths of the subtable columns.   [This doesn't work
        // 100% - I'm not sure why - but I have left the code in]
        ?>
        maintable.find('tr').eq(0).find('th').each(function(i){
            var def = {};
            switch (i)
            {
              case 0: <?php // expand control ?>
                def.orderable = false;
                break;
              case 5: <?php // start-time ?>
                def.sType = "title-numeric";
                break;
            }
            def.width = ($(this).outerWidth()) + "px";
            columns.push(def);
          });
        
        nTr.hide();
        pendingTable.row(nTr).child(subtable.get(0)).show();
        subtable.closest('td').addClass('table_container');

        subDataTable = $('#' + subtableId).DataTable({autoWidth: false,
                                                      paging: false,
                                                      dom: 't',
                                                      order: [[5, 'asc']],
                                                      columns: columns});

        $('#subtable_' + serial + '_wrapper').hide().slideDown();
      });
                  
    <?php // Turn the table into a datatable ?>
    var tableOptions = {order: [[5, 'asc']]};
    tableOptions.columnDefs = [{targets: 0, orderable: false}];
    tableOptions.columnDefs.push(getSTypes(maintable));
    <?php
    // For some reason I don't understand, fnOpen() doesn't seem to work when
    // using FixedColumns.   We also have to turn off bStateSave.  I have raised
    // this on the dataTables forum.  In the meantime we comment out the FixedColumns.
    ?>
    tableOptions.stateSave = false;

    <?php
    // Remove the first column from the column visibility
    // list because it is the control column
    ?>
    tableOptions.oColVis = {aiExclude: [0]};
    <?php
    // and stop the first column being reordered
    ?>
    tableOptions.oColReorder = {"iFixedColumns": 1};
    var pendingTable = makeDataTable('#pending_table', tableOptions);
  }  // if (!lteie6)
};
