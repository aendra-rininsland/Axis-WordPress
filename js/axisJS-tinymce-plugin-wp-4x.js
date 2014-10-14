/*global tinymce, axisWP*/
/**
 *  AxisJS TinyMCE plugin
 */

tinymce.PluginManager.add('Axis', function(editor) {
  var toolbarActive = false;

  // Add a button that opens a window
  editor.addButton('Axis', {
    text: false,
    icon: 'icon dashicons-chart-area',
    onclick: function() {
      // Open window
      editor.windowManager.open({
        title: 'Axis',
        width: jQuery(window).width() - 100,
        height: jQuery(window).height() - 100,
        url: axisWP.axisJSPath,
        buttons: [
          {
            text: 'Cancel',
            onclick: 'close'
          }
        ]
      },
      {
        axisWP: axisWP
      });
    }
  });

  function editImage( img ) {
    // Open window
    editor.windowManager.open({
      title: 'Axis',
      width: jQuery(window).width() - 100,
      height: jQuery(window).height() - 100,
      url: axisWP.axisJSPath,
      buttons:
        [
          {
            text: 'Cancel',
            onclick: 'close'
          }
        ]
      },
      {
        axisJS: img.dataset.axisjs,
        axisWP: axisWP
      }
    );
  }

  function removeImage( node ) {
    var wrap;

    if ( node.nodeName === 'DIV' && editor.dom.hasClass( node, 'mceTemp' ) ) {
      wrap = node;
    } else if ( node.nodeName === 'IMG' || node.nodeName === 'DT' || node.nodeName === 'A' ) {
      wrap = editor.dom.getParent( node, 'div.mceTemp' );
    }

    if ( wrap ) {
      if ( wrap.nextSibling ) {
        editor.selection.select( wrap.nextSibling );
      } else if ( wrap.previousSibling ) {
        editor.selection.select( wrap.previousSibling );
      } else {
        editor.selection.select( wrap.parentNode );
      }

      editor.selection.collapse( true );
      editor.nodeChanged();
      editor.dom.remove( wrap );
    } else {
      editor.dom.remove( node );
    }
    removeToolbar();
  }

  function addToolbar( node ) {
    var rectangle,
        toolbarHtml,
        toolbar,
        left,
        dom = editor.dom;

    // Don't add to placeholders
    if ( ! node || !dom.hasClass(node, 'axisChart') || isPlaceholder( node ) ) {
      return;
    }

    removeToolbar();

    dom.setAttrib( node, 'data-wp-chartselect', 1 );
    rectangle = dom.getRect( node );

    toolbarHtml = '<i class="dashicons dashicons-chart-area edit" data-mce-bogus="1"></i>' +
      '<i class="dashicons dashicons-no-alt remove" data-mce-bogus="1"></i>';

    toolbar = dom.create( 'p', {
      'id': 'wp-image-toolbar',
      'data-mce-bogus': '1',
      'contenteditable': false
    }, toolbarHtml );

    if ( editor.rtl ) {
      left = rectangle.x + rectangle.w - 82;
    } else {
      left = rectangle.x;
    }

    editor.getBody().appendChild( toolbar );
    dom.setStyles( toolbar, {
      top: rectangle.y,
      left: left
    });

    toolbarActive = true;
  }

  function removeToolbar() {
    var toolbar = editor.dom.get( 'wp-image-toolbar' );

    if ( toolbar ) {
      editor.dom.remove( toolbar );
    }

    editor.dom.setAttrib( editor.dom.select( 'img[data-wp-chartselect]' ), 'data-wp-chartselect', null );

    toolbarActive = false;
  }

  function isPlaceholder( node ) {
    var dom = editor.dom;

    if ( /*dom.hasClass( node, 'mceItem' ) ||*/ dom.getAttrib( node, 'data-mce-placeholder' ) ||
      dom.getAttrib( node, 'data-mce-object' ) ) {

      return true;
    }

    return false;
  }

  editor.on( 'mousedown', function( event ) {
    if ( editor.dom.getParent( event.target, '#wp-image-toolbar' ) ) {
      if ( tinymce.Env.ie ) {
        // Stop IE > 8 from making the wrapper resizable on mousedown
        event.preventDefault();
      }
    } else if ( event.target.nodeName !== 'IMG' ) {
      removeToolbar();
    }
  });

  editor.on( 'mouseup', function( event ) {
    var image,
      node = event.target,
      dom = editor.dom;

    // Don't trigger on right-click
    if ( event.button && event.button > 1 ) {
      return;
    }

    if ( node.nodeName === 'I' && dom.getParent( node, '#wp-image-toolbar' ) ) {
      image = dom.select( 'img[data-wp-chartselect]' )[0];

      if ( image ) {
        editor.selection.select( image );
        if ( dom.hasClass( node, 'remove' ) ) {
          removeImage( image );
        } else if ( dom.hasClass( node, 'edit' ) ) {
          editImage( image );
        }
      }
    } else if ( node.nodeName === 'IMG' && ! editor.dom.getAttrib( node, 'data-wp-chartselect' ) && ! isPlaceholder( node ) ) {
      addToolbar( node );
    } else if ( node.nodeName !== 'IMG' ) {
      removeToolbar();
    }
  });

  editor.on( 'cut', function() {
    removeToolbar();
  });


  // This might not be needed.
  editor.on( 'PostProcess', function( event ) {
    if ( event.get ) {
      event.content = event.content.replace( / data-wp-chartselect="1"/g, '' );
    }
  });

});
