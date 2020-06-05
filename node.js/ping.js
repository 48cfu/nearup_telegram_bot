const nearApi  = require('near-api-js')
const settings = require('./settings');

const myArgs = process.argv.slice(2);
const accountId = myArgs[0];
const privateKey = myArgs[1];
const contractName = myArgs[2];


process.env = {
    NODE_URL: 'http://127.0.0.1:3030'
};

const keyPair = nearApi.utils.KeyPair.fromString(privateKey);
const keyStore = new nearApi.keyStores.InMemoryKeyStore();

keyStore.setKey("default", accountId, keyPair);

const nearPromise = (async () => {
    const near = await nearApi.connect({
        networkId: "default",
        deps: {keyStore},
        masterAccount: accountId,
        nodeUrl: process.env.NODE_URL
    });

    const account = await near.account(accountId);

    const data = await account.functionCall(contractName, "ping", {});

    if (data) {
        try {
            if (data["status"].hasOwnProperty("SuccessValue"))
                console.log("Success!");
            else {
                console.log("Transaction Error");
            }
        } catch (e) {
            console.log("Transaction processed");
        }
    } else
        console.log("Unhandled error");
    return near;
})();