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

  var display_tabs = jQuery('.nav-tabs > .nav-item > .nav-link');
  jQuery.each(display_tabs, function() {
    jQuery(this).click(displayTab);
  });
  jQuery('[href="#species"]').trigger('click');

  initReferencePages();
});

var display_pages = {
  "trees": 0,
  "samples": 0,
  "dart": 0,
  "isotope": 0,
  "genetic": 0,
  "anatomy": 0,
};

function displayTab() {
  var clicked_tab = jQuery(this)[0];
  var path = clicked_tab.pathname;
  var display_type = clicked_tab.hash.substr(1);
  var page = display_pages[display_type];
  if (clicked_tab.hash.match(/#(.*):(.*)/) !== null) {
    display_type = clicked_tab.hash.match(/#(.*):(.*)/)[1];
    page = clicked_tab.hash.match(/#(.*):(.*)/)[2];
    display_pages[display_type] = page;
  }
  else {
    if (jQuery('#' + display_type)[0].innerHTML !== "") {
      // If we aren't loading a new page and we already have data for this tab,
      // then we don't need to change any of the HTML.
      return;
    }
  }
  jQuery('#' + display_type)[0].innerHTML = "Loading...";

  var request = jQuery.post(path + '/' + display_type, {
    page: page
  });

  request.done(function (data) {
    jQuery('#' + display_type)[0].innerHTML = data;
    var display_pagers = jQuery('#' + display_type + ' > div > ul');
    if (display_pagers.length > 0) {
      var pages = jQuery('#' + display_type + ' > div > ul > li > a');
      jQuery.each(pages, function() {
        var page = 0;
        if (this.search.match(/\?page=(.*)/) !== null) {
          page = this.search.match(/\?page=(.*)/)[1];
        }
        this.href = '#' + display_type + ':' + page;
        jQuery(this).click(displayTab);
      });
    }
  });
}

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
  var values = $('input').filter(function() { return this.id.match(/edit-value/); });
  if (values.length == 0) {
    values = $('select').filter(function() { return this.id.match(/edit-value/); });
  }
  var request = $.post(path, {
    data_type: $('select').filter(function() { return this.id.match(/edit-data-type/); })[0].value,
    value: values[0].value,
    attribute: $('select').filter(function() { return this.id.match(/edit-attribute/); })[0].value,
    page: page
  });

  request.done(function (data) {
    $('#gttn-tpps-reference-table')[0].innerHTML = data;
    initReferencePages();
  });
}
