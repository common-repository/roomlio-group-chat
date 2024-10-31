(function () {
  let hmacInputEl = document.getElementById('roomlio-hmac-key');
  let hmacInputIconEl = document.getElementById('roomlio-hmac-key-icon');

  // toggles the HMAC Key input from a text and password input.
  // hides the key by default since it is sensitive data.
  if (hmacInputIconEl && hmacInputEl) {
    hmacInputIconEl.addEventListener('click', () => {
      if (hmacInputEl.type === 'text') {
        hmacInputEl.type = 'password';
        hmacInputIconEl.src = hmacInputIconEl.src.replace(
          'eye-off.svg',
          'eye.svg',
        );
      } else {
        hmacInputEl.type = 'text';
        hmacInputIconEl.src = hmacInputIconEl.src.replace(
          'eye.svg',
          'eye-off.svg',
        );
      }
    });
  }

  // Onboarding flow on settings page.
  let hasRmlAcctBtnEl = document.getElementById('rml-acct-yes');
  let doesntHaveRmlAcctBtnEl = document.getElementById('rml-acct-no');
  let rmlSettingsFormEl = document.getElementById('rml-settings-form');
  let rmlAcctConfirmEl = document.getElementById('rml-acct-confirm');
  let rmlSetupStepsEl = document.getElementById('rml-setup-steps');
  let hasRmlAcct = localStorage.getItem('has-rml-acct') === 'yes';

  if (rmlSettingsFormEl && rmlAcctConfirmEl && rmlSetupStepsEl) {
    if (hasRmlAcct) {
      rmlSettingsFormEl.style.display = 'block';
      rmlSetupStepsEl.style.display = 'none';
      rmlAcctConfirmEl.style.display = 'none';
    }

    if (hasRmlAcctBtnEl) {
      hasRmlAcctBtnEl.addEventListener('click', () => {
        rmlSettingsFormEl.style.display = 'block';
        rmlAcctConfirmEl.style.display = 'none';
        rmlSetupStepsEl.style.display = 'none';
        hasRmlAcct = true;
        localStorage.setItem('has-rml-acct', 'yes');
      });
    }

    if (doesntHaveRmlAcctBtnEl) {
      doesntHaveRmlAcctBtnEl.addEventListener('click', () => {
        rmlAcctConfirmEl.style.display = 'none';
        rmlSetupStepsEl.style.display = 'block';
        rmlSettingsFormEl.style.display = 'block';
        localStorage.setItem('has-rml-acct', 'no');
      });
    }
  }

  let searchParams = new URLSearchParams(location.search);
  let roomKeyInputEl = document.getElementById('roomlio-room-key');

  const onAddNewRoomPage = () => {
    return (
      roomKeyInputEl &&
      searchParams.get('post_type') == 'roomlio_room' &&
      location.pathname == '/wp-admin/post-new.php'
    );
  };

  const onRoomListPage = () => {
    return (
      searchParams.get('post_type') == 'roomlio_room' &&
      location.pathname == '/wp-admin/edit.php'
    );
  };

  // make sure we're on the "Add New Room" page before we add the listener
  if (onAddNewRoomPage()) {
    let publishBtnEl = document.getElementById('publish');

    // change the default publish button to read 'Add Room'.
    publishBtnEl.value = 'Add Room';

    // hide the Add New Room form if they don't have a PK set in the settings.
    if (rmlSettingsData.hasPK === 'false') {
      let addRoomFormEl = document.getElementById('poststuff');
      if (addRoomFormEl) {
        addRoomFormEl.style.display = 'none';
      }
    }
  }

  // hide the room list form if they don't have a PK set in the settings.
  if (onRoomListPage() && rmlSettingsData.hasPK === 'false') {
    let roomListEl = document.getElementById('posts-filter');
    if (roomListEl) {
      roomListEl.style.display = 'none';
    }
  }
})();
