import SharedReference from '../../utilities/SharedReference';
import ApiConnector from '../ApiConnector';
import IAccountApi, {
    FindResponse,
    IFindRequest,
    IGetAvatarRequest,
    IGetAvatarResponse,
    ISaveAvatarRequest,
    ISaveAvatarResponse,
    ISaveProfileRequest,
    ISaveProfileResponse,
} from './IAccountApi';

export default class AccountApiConnector implements IAccountApi {
    constructor(private _api = new SharedReference(ApiConnector)) {
    }

    public find(args: IFindRequest) {
        return this._api.value.post<FindResponse>('account/find', args);
    }

    public getAvatar(args: IGetAvatarRequest) {
        return this._api.value.get<IGetAvatarResponse>(`account/${args.accountId}/avatar`);
    }

    public saveAvatar(args: ISaveAvatarRequest) {
        const formData = new FormData();
        formData.append('avatar', args.file, args.file.name);

        return this._api.value.post<ISaveAvatarResponse>(`account/avatar/edit/${args.accountId}`, formData);
    }

    public saveProfile(args: ISaveProfileRequest) {
        return this._api.value.post<ISaveProfileResponse>(`account/edit/${args.accountId}`, args);
    }
}
