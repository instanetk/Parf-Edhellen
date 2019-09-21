import {
    ReduxThunk,
    ReduxThunkDispatch,
} from '@root/_types';
import { handleValidationErrors } from '@root/components/Form/Validation';
import ContributionResourceApiConnector from '@root/connectors/backend/ContributionResourceApiConnector';
import GlossResourceApiConnector from '@root/connectors/backend/GlossResourceApiConnector';
import { IGlossEntity } from '@root/connectors/backend/GlossResourceApiConnector._types';
import SharedReference from '@root/utilities/SharedReference';

import Actions from './Actions';

export default class GlossActions {
    constructor(
        private _glossApi = SharedReference.getInstance(GlossResourceApiConnector),
        private _contributionApi = SharedReference.getInstance(ContributionResourceApiConnector)) {}

    public gloss(glossId: number): ReduxThunk {
        return async (dispatch: ReduxThunkDispatch) => {
            const gloss = await this._glossApi.gloss(glossId);
            dispatch(this.setGloss(gloss));
        };
    }

    public setGloss(gloss: IGlossEntity) {
        return {
            gloss,
            type: Actions.ReceiveGloss,
        };
    }

    public setField(field: keyof IGlossEntity, value: any) {
        return {
            field,
            type: Actions.SetField,
            value,
        };
    }

    public saveGloss(gloss: IGlossEntity) {
        return (dispatch: ReduxThunkDispatch) => handleValidationErrors(dispatch, async () => {
            const response = await this._contributionApi.saveGloss(gloss);
            if (response.url) {
                window.location.href = response.url;
            }
        });
    }
}
