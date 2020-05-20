'use strict';
const nearApi = require('near-api-js')
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};

const nodeUrl = 'http://127.0.0.1:3030';

const provider = new nearApi.providers.JsonRpcProvider(nodeUrl);

(async () => {
    let validators = await provider.validators(null);
    const info = await findSeatPrice(validators["current_validators"], 100);
    const sitPrice = info.toString(10);
    console.log(sitPrice.substr(0, sitPrice.length - 24));
})();

const bn_js_1 = __importDefault(require("bn.js"));

function findSeatPrice(validators, numSeats) {
    const stakes = validators.map(v => new bn_js_1.default(v.stake, 10)).sort((a, b) => a.cmp(b));
    const num = new bn_js_1.default(numSeats);
    const stakesSum = stakes.reduce((a, b) => a.add(b));
    if (stakesSum.lt(num)) {
        throw new Error('Stakes are below seats');
    }
    // assert stakesSum >= numSeats
    let left = new bn_js_1.default(1), right = stakesSum.add(new bn_js_1.default(1));
    while (!left.eq(right.sub(new bn_js_1.default(1)))) {
        const mid = left.add(right).div(new bn_js_1.default(2));
        let found = false;
        let currentSum = new bn_js_1.default(0);
        for (let i = 0; i < stakes.length; ++i) {
            currentSum = currentSum.add(stakes[i].div(mid));
            if (currentSum.gte(num)) {
                left = mid;
                found = true;
                break;
            }
        }
        if (!found) {
            right = mid;
        }
    }
    return left;
}


