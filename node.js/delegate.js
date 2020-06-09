const nearApi = require('near-api-js')
const myArgs = process.argv.slice(2);
const accountId = myArgs[0];
const privateKey = myArgs[1];
const recipient = myArgs[2];
const amount = myArgs[3];

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

    const deposit = await account.functionCall(recipient, "deposit", {}, '100000000000000', amount + "000000000000000000000000");

    if (deposit) {
        try {
            if (deposit["status"].hasOwnProperty("SuccessValue")) {
                console.log("Deposit successful!\n");

                const stake = await account.functionCall(recipient, "stake", {"amount": amount + "000000000000000000000000"}, '100000000000000', '0');
                try {
                    if (stake["status"].hasOwnProperty("SuccessValue")) {
                        console.log("Stake successful!");
                    }
                    else {
                        console.log("Stake Error");
                    }
                } catch (e) {
                    console.log("Stake transaction processed with unknown result. Check delegation status in explorer and try /stake command to proceed.");
                }

            }
            else {
                console.log("Deposit Error. Check delegation status in explorer and try /stake command to proceed.");
            }
        } catch (e) {
            console.log("Deposit transaction processed with unknown result");
        }
    } else
        console.log("Unhandled error.");

    return near;
})();