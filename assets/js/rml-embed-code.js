(async function () {
  window.rmlCalls = window.rmlCalls || {};
  function rml() {
    let ri = arguments[1].roomElementID;
    if (!rmlCalls[ri]) rmlCalls[ri] = [];
    rmlCalls[ri].push(arguments);
  }

  window.rml = rml;

  const rmlConfig = (widgetID) => {
    rml('config', {
      options: {
        embedPosition: 'inline',
      },
      widgetID: widgetID,
      pk: rmlEmbedCodeData.pk,
      roomElementID: rmlEmbedCodeData.roomElementID,
    });

    // need a nonce and an HMAC key to call rml('registerSecure').
    // no nonce means the user is not logged into wordpress and
    // therefore will need to be identified insecurely with geo location and not wordpress..
    if (rmlEmbedCodeData.nonce && rmlEmbedCodeData.hasHMAC === 'true') {
      registerSecure(rmlEmbedCodeData.nonce);
    } else {
      registerInsecure();
    }
  };

  const registerInsecure = () => {
    rml('register', {
      options: {
        roomKey: rmlEmbedCodeData.roomKey,
        roomName: rmlEmbedCodeData.roomName,
      },
      roomElementID: rmlEmbedCodeData.roomElementID,
    });
  };

  const registerSecure = async (nonce) => {
    let data = new FormData();
    data.append('roomKey', rmlEmbedCodeData.roomKey);
    data.append('roomName', rmlEmbedCodeData.roomName);
    data.append('_wpnonce', nonce);

    // call the custom wordpress endpoint fetch the secure payload for the `registerSecure` call.
    // https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
    const resp = await fetch('/wp-json/roomlio-group-chat/v1/secure_identify', {
      method: 'POST',
      body: data,
    });

    // if we get a bad response, eg. bad auth, display an error to the user in the page
    if (resp.status >= 400) {
      const body = await resp.text();
      window.document.body.insertAdjacentHTML(
        'afterbegin',
        `<div id="myID" style="padding: 10px;background: red;color: white;width: 100%; height: 50px;">An Unknown Roomlio Group Chat Error Occurred: ${body}</div>`,
      );
      return;
    }

    const payload = await resp.json();

    window.rml('registerSecure', {
      options: payload.payloadStr,
      payloadMAC: payload.payloadMAC,
      roomElementID: rmlEmbedCodeData.roomElementID,
    });
  };

  // retrieve the default widgetID so we can make the rml('config') call.
  const getEmbedConfig = async () => {
    const configUrl = `https://embed.roomlio.com/config/${rmlEmbedCodeData.pk}`;
    const resp = await fetch(configUrl);
    const config = await resp.json();
    rmlConfig(config.widgetID);
  };

  await getEmbedConfig();

  var embedCDN = document.createElement('script');
  embedCDN.setAttribute('src', 'https://embed.roomlio.com/embed.js');
  document.body.appendChild(embedCDN);
})();
