if (typeof($) === "undefined") {
  $ = jQuery;
}

$(document).ready(function () {
  $('#edit-search').attr('type', 'button');
  $('#edit-search').click(referenceSearch);
  $('#gttn-tpps-reference-search').submit(function() {
    referenceSearch();
    return false;
  });

  initReferencePages();
});

function initReferencePages() {
  var ref_pages = $('#gttn-tpps-reference-table > div > ul > li > a');
  if (ref_pages.length > 0) {
    $.each(ref_pages, function() {
      var page = 0;
      if (this.search.match(/\?page=(.*)/) !== null) {
        page = this.search.match(/\?page=(.*)/)[1];
      }
      this.href = '#top:' + page;
      $(this).click(referenceSearch);
    });
  }
}

function referenceSearch() {
  var path = '/reference/top';
  var page = 0;
  if (this.hash != null && this.hash.match(/#.*:(.*)/) != null) {
    page = this.hash.match(/#.*:(.*)/)[1];
  }

  $('#gttn-tpps-reference-table')[0].innerHTML = 'Loading...';
  var request = $.post(path, {
    data_type: $('select').filter(function() { return this.id.match(/edit-data-type/); })[0].value,
    value: $('input').filter(function() { return this.id.match(/edit-value/); })[0].value,
    attribute: $('select').filter(function() { return this.id.match(/edit-attribute/); })[0].value,
    page: page
  });

  request.done(function (data) {
    $('#gttn-tpps-reference-table')[0].innerHTML = data;
    initReferencePages();
  });
}
