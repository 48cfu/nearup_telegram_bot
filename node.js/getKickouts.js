'use strict';
const nearApi = require('near-api-js')

const nodeUrl = 'http://127.0.0.1:3030';
const provider = new nearApi.providers.JsonRpcProvider(nodeUrl);

(async () => {
    let validators = await provider.validators(null);
    console.log(JSON.stringify( validators["prev_epoch_kickout"]));
})();
