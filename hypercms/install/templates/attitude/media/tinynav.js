/*! http://tinynav.viljamis.com v1.03 by @viljamis */
/*! Further modified by Kailash Bijayananda-www.facebook.com/FriedDust for our theme's adjustment. */
function abc(e) {
var count = 0;
if (!e.hasClass('root'))
{
if (e.is('ul'))
count++;
return count + abc(e.parent());
}
return count;
}
(function ($, window, i) {
  $.fn.tinyNav = function (options) {

    // Default settings
    var settings = $.extend({
      'active' : 'current-menu-item', // String: Set the "active" class
      'header' : false // Boolean: Show header instead of the active item
    }, options);
    
    var counter = -1;

    return this.each(function () {
      // Used for namespacing
      i++;

      var $nav = $(this),
        // Namespacing
        namespace = 'tinynav',
        namespace_i = namespace + i,
        l_namespace_i = '.l_' + namespace_i,
        $select = $('<select/>').addClass(namespace + ' ' + namespace_i);

      if ($nav.is('ul,ol')) {
      
        if (settings.header) {
          $select.append(
            $('<option/>').text('Navigation')
          );
        }

        // Build options
        var options = '';
    
        $nav
          .addClass('l_' + namespace_i)
          .find('a')
          .each(function () {
            var y = abc($(this));
            var space = "";
            for (var x=0; x<y; x++)
              space += "--";
              
            options +=
              '<option value="' + $(this).attr('href') + '">' + space +
              $(this).text() +
              '</option>';
          });

        // Append options into a select
        $select.append(options);

        // Select the active item
        if (!settings.header) {
          $select
            .find(':eq(' + $(l_namespace_i + ' li')
            .index($(l_namespace_i + ' li.' + settings.active)) + ')')
            .attr('selected', true);
        }

        // Change window location
        $select.change(function () {
          window.location.href = $(this).val();
        });

        // Inject select
        $(l_namespace_i).after($select);

      }

    });

  };
})($, this, 0);

// Tinynav
    $(function () {

      // TinyNav.js 1
      $('#access .root').tinyNav({
        active: 'current-menu-item'
      });

    });