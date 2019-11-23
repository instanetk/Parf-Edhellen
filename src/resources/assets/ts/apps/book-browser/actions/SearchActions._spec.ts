import { expect } from 'chai';
import * as sinon from 'sinon';

import BookApiConnector from '@root/connectors/backend/BookApiConnector';
import SearchActions from '../actions/SearchActions';

import { ISearchAction } from '../reducers/SearchReducer._types';
import Actions from './Actions';

describe('apps/book-browser/reducers/SearchReducer', () => {
    const TestSearchResults = [
        {
            k: 'keyword1',
            nk: 'keyword1-nk',
            ok: 'keyword1-ok',
        },
        {
            k: 'keyword2',
            nk: 'keyword2-nk',
            ok: 'keyword2-ok',
        },
    ];

    let sandbox: sinon.SinonSandbox;
    let actions: SearchActions;

    before(() => {
        sandbox = sinon.createSandbox();

        const api = sinon.createStubInstance(BookApiConnector);
        api.find.callsFake(() => Promise.resolve(TestSearchResults));
        actions = new SearchActions(api as any, null /* LanguageConnector */);
    });

    afterEach(() => {
        sandbox.restore();
    });

    it('searches for word', async () => {
        const fakeDispatch = sandbox.spy();

        const searchArgs: ISearchAction = { word: 'hello' };
        const action = actions.search(searchArgs);
        await action(fakeDispatch);

        expect(fakeDispatch.callCount).to.equal(2);
        expect(fakeDispatch.firstCall.args.length).to.equal(1);
        expect(fakeDispatch.firstCall.args[0]).to.deep.equal({
            type: Actions.RequestSearchResults,
            ...searchArgs,
        });
        expect(fakeDispatch.secondCall.args.length).to.equal(1);
        expect(fakeDispatch.secondCall.args[0].type).to.equal(Actions.ReceiveSearchResults);

        const items = TestSearchResults.map((r) => ({
            normalizedWord: r.nk,
            originalWord: r.ok,
            word: r.k,
        }));
        const actual = fakeDispatch.secondCall.args[0].searchResults.map((r: any) => {
            const props = Object.keys(r).filter((prop: string) => prop !== 'id');
            return props.reduce((map, prop) => ({ ...map, [prop]: r[prop] }), {});
        });
        expect(actual).to.deep.equal(items);
    });
});
