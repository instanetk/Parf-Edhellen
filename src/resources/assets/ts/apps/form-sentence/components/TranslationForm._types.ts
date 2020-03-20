import { IProps as IParentProps } from '../containers/SentenceForm._types';

export interface IProps {
    paragraphs: IParentProps['sentenceParagraphs'];
    translations: IParentProps['sentenceTranslations'];
}
