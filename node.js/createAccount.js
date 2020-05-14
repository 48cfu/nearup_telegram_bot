const nearApi  = require('near-api-js')
const settings = require('./settings');

const myArgs = process.argv.slice(2);
const recipientId = myArgs[0];

const masterKey = settings.masterKey;


const keypair = nearApi.utils.KeyPair.fromRandom('ed25519')
const publickey = keypair.publicKey.toString();
const privateKey = keypair.secretKey;

process.env = {
    NODE_URL: 'http://127.0.0.1:3030'
};

let accountId = "zavodil.betanet";

const keyPair =  nearApi.utils.KeyPair.fromString(masterKey);

const keyStore = new nearApi.keyStores.InMemoryKeyStore();
keyStore.setKey("default", accountId, keyPair);

const nearPromise = (async () => {
    const near = await nearApi.connect({
        networkId: "default",
        deps: { keyStore },
        masterAccount: accountId,
        nodeUrl: process.env.NODE_URL
    });

    const account = await near.account(accountId);

    const res = await account.createAccount(recipientId, publickey, '16552803572267293929962');

    try{
        if(res['status'].hasOwnProperty('SuccessValue'))
            console.log(JSON.stringify({public: publickey, private: privateKey}));
    }
    catch (e) {

    }
    return near;
})();
