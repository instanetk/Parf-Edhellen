import { IReduxAction } from '@root/_types';
import { IGloss } from '@root/connectors/backend/IWordFinderApi';

export interface IGameAction extends IReduxAction {
    glossId?: number;
    glossary?: IGloss[];
    parts?: string[];
    selectedPartId?: number;
    stage?: GameStage;
}

export const enum GameStage {
    Loading = 0,
    Running = 1,
    Success = 2,
}
