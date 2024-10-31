// https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/writing-your-first-block-type/#registering-the-block
(function (blocks, element, blockEditor) {
  var el = element.createElement;
  var useBlockProps = blockEditor.useBlockProps;

  let editorBlockStyle = {
    backgroundColor: '#fff',
    color: '#212121',
  };
  let pageBlockStyle = {
    weight: '0px',
    height: '0px',
  };

  let roomlioLogo = el(
    'svg',
    {
      viewBox: '0 0 512 512',
      fill: 'none',
      xmlns: 'http://www.w3.org/2000/svg',
    },
    el('rect', { width: '512', height: '512', rx: '117', fill: '#6C60F3' }),
    el('rect', {
      x: '86',
      y: '346',
      width: '164',
      height: '80',
      rx: '25',
      fill: 'white',
    }),
    el('rect', {
      x: '219',
      y: '216',
      width: '207',
      height: '80',
      rx: '25',
      fill: 'white',
    }),
    el('rect', {
      x: '86',
      y: '86',
      width: '297',
      height: '80',
      rx: '25',
      fill: 'white',
    }),
  );

  let newTabIcon = el(
    'svg',
    {
      xmlns: 'http://www.w3.org/2000/svg',
      viewBox: '0 0 24 24',
      width: '18',
      height: '18',
      role: 'img',
      ariaHidden: 'true',
      focusable: 'false',
    },
    el('path', {
      d: 'M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z',
    }),
  );

  /* 
  rmlBlockTypeData is being passed into this script via the wp_localize_script call in rml-function.php.
  rmlBlockTypeData: { rooms: [] }
  */

  blocks.registerBlockType('roomlio/room', {
    apiVersion: 2,
    title: 'Roomlio Room',
    icon: roomlioLogo,
    category: 'widgets',
    description: 'Add an already created Roomlio room to this page',
    // think of attributes as React's state variables
    attributes: {
      addedRoom: { type: 'object', default: null },
      selectedRoom: { type: 'object', default: rmlBlockTypeData.rooms[0] },
    },
    // edit is the function that renderes the admin edit block.
    edit: function (props) {
      // clone props so it's immutable and there are no references to the original props.
      // prevents the save function from being invalid.
      let newProps = props;
      let blockProps;
      if (newProps.attributes.addedRoom === null) {
        blockProps = useBlockProps({
          style: editorBlockStyle,
          className: 'components-placeholder wp-block-embed is-large',
        });
      } else {
        blockProps = useBlockProps({
          style: {
            height: newProps.attributes.addedRoom.height
              ? newProps.attributes.addedRoom.height
              : '100%',
            width: newProps.attributes.addedRoom.width
              ? newProps.attributes.addedRoom.width
              : '100%',
            backgroundColor: '#fff',
          },
          className: 'room-placeholder-container',
        });
      }

      function skeletonMessage() {
        return el(
          'div',
          { class: 'skeleton-message' },
          el('div', { class: 'header' }),
          el(
            'div',
            { class: 'body' },
            el('div', { class: 'text' }),
            el('div', { class: 'text short' }),
            el('div', { class: 'text' }),
          ),
        );
      }

      // if user has added a room, display a placeholder so they know where the room will be and how big it will be.
      if (newProps.attributes.addedRoom !== null) {
        return el(
          'div',
          blockProps,
          el(
            'div',
            { class: 'components-placeholder__label' },
            el(
              'span',
              { class: 'block-editor-block-icon has-colors' },
              roomlioLogo,
            ),
            el(
              'span',
              {},
              `${newProps.attributes.addedRoom?.post_title} will be displayed here`,
            ),
          ),
          skeletonMessage(),
          skeletonMessage(),
          skeletonMessage(),
          skeletonMessage(),
          el('div', { class: 'skeleton-input' }),
        );
      }

      function addRoomToPage(e) {
        e.preventDefault();
        newProps.setAttributes({ addedRoom: newProps.attributes.selectedRoom });
      }
      function getRoom(id) {
        return rmlBlockTypeData.rooms.find((r) => r.ID == id);
      }
      function updateSelectedRoom(e) {
        newProps.setAttributes({ selectedRoom: getRoom(e.target.value) });
      }
      function optionsElements() {
        let options = [];
        for (let i = 0; i < rmlBlockTypeData.rooms.length; i++) {
          options.push(
            el(
              'option',
              { value: rmlBlockTypeData.rooms[i].ID },
              rmlBlockTypeData.rooms[i].post_title,
            ),
          );
        }
        return options;
      }
      function selectEl() {
        return el(
          'div',
          blockProps,
          el(
            'div',
            { class: 'components-placeholder__label' },
            el(
              'span',
              { class: 'block-editor-block-icon has-colors' },
              roomlioLogo,
            ),
            'Roomlio Room',
          ),
          el(
            'div',
            { class: 'components-placeholder__instructions' },
            'Select an already created room that you want to display on this page.',
          ),
          el(
            'div',
            { class: 'components-placeholder__fieldset' },
            el(
              'form',
              {},
              el(
                'select',
                {
                  onChange: updateSelectedRoom,
                  value: newProps.attributes.selectedRoom?.ID,
                },
                optionsElements(),
              ),
              el(
                'button',
                {
                  onClick: addRoomToPage,
                  disabled: rmlBlockTypeData.rooms?.length === 0,
                  class: 'button button-primary button-large',
                },
                'Add Room',
              ),
            ),
            el(
              'div',
              { class: 'components-placeholder__learn-more' },
              el('span', {}, 'Not seeing the right room in the dropdown? '),
              el(
                'a',
                {
                  href: '/wp-admin/post-new.php?post_type=roomlio_room',
                  target: '_blank',
                  rel: 'external noreferrer nopener',
                  class: 'components-external-link underline-link',
                },
                'Create a new room.',
                el('span', {}, newTabIcon),
              ),
            ),
          ),
        );
      }
      return selectEl();
    },
    // the save function is what runs when a user views the block on the actual web.
    save: function (props) {
      var blockProps = useBlockProps.save({ style: pageBlockStyle });
      return el('p', blockProps, props.attributes.addedRoom?.shortcode);
    },
  });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor);
