import * as sinon from 'sinon';
import axios from 'axios';
import { expect } from 'chai';

import ApiConnector from '../connectors/api';

describe('connectors/api', () => {
    const ApiPrefix = '/api/unit-test';
    const ApiMethod = 'test/now';
    const ApiErrorMethod = 'error';
    const ApiPayload = {
        complex: {
            x: 1
        }
    };
    const ApiResponse = {
        data: true
    };

    let sandbox: sinon.SinonSandbox;
    let api: ApiConnector;

    before(() => {
        sandbox = sinon.createSandbox();
        api = new ApiConnector(ApiPrefix, ApiErrorMethod, 421);
    });

    afterEach(() => {
        sandbox.restore();
    });

    it('supports DELETE, HEAD, GET', async () => {
        const verbs = ['delete', 'head', 'get'];

        for (const verb of verbs) {
            sandbox.stub(axios, <any> verb)
                .callsFake((path, config) => {
                    expect(path).to.equal(`${ApiPrefix}/${ApiMethod}`);
                    expect(config).to.deep.equal(api.config);
                    return Promise.resolve(ApiResponse);
                });

            const result = await (<any> api)[verb](ApiMethod);
            expect(result).to.equal(ApiResponse.data);
        }
    });

    it('supports POST, PUT', async () => {
        const verbs = ['post', 'put'];

        for (const verb of verbs) {
            sandbox.stub(axios, <any> verb)
                .callsFake((path, payload, config) => {
                    expect(path).to.equal(`${ApiPrefix}/${ApiMethod}`);
                    expect(payload).to.equal(ApiPayload);
                    expect(config).to.deep.equal(api.config);

                    return Promise.resolve(ApiResponse);
                });

            const result = await (<any> api)[verb](ApiMethod, ApiPayload);
            expect(result).to.equal(ApiResponse.data);
        }
    });

    it('can handle errors', () => {
        const faultyResponse = {
            response: {
                status: 500,
                headers: {
                    'X-Caused-By': 'unit-test'
                },
                data: {
                    dummy: true
                }
            }
        };

        sandbox.stub(axios, 'get')
            .callsFake(() => {
                return Promise.reject(faultyResponse);
            });
        
        sandbox.stub(axios, 'post')
            .callsFake((method, payload) => {
                expect(method).to.equal(`${ApiPrefix}/${ApiErrorMethod}`);
                expect(payload.category).to.equal('frontend');
                expect(payload.url).to.equal(ApiMethod);
                expect(payload.error).to.be.string;

                return Promise.resolve(ApiResponse);
            });

        api.get(ApiMethod)
            .catch(() => true); // silence exception
    });

    it('can report errors', async () => {
        const message = 'something went wrong';
        const url = 'http://localhost/unit-tests';
        const error = 'stacktrace missing';
        const category = 'unit-tests';

        sandbox.stub(axios, 'post')
            .callsFake((method, payload) => {
                expect(method).to.equal(`${ApiPrefix}/${ApiErrorMethod}`);
                expect(payload).to.deep.equal({ message, url, error, category });
                return Promise.resolve(ApiResponse);
            });

        const result = await api.error(message, url, error, category);
        expect(result).to.equal(ApiResponse.data);
    });
});
