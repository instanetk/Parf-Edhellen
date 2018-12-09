import React from 'react';

import {
    IFragmentInSentenceState,
    ITextState,
} from '../reducers/FragmentsReducer._types';
import {
    IProps,
    IRenderArgs,
} from './TextInspectorView._types';

import Fragment from './Fragment';
import Paragraph from './Paragraph';
import ParagraphGroup from './ParagraphGroup';

export default class TextInspectorView extends React.PureComponent<IProps> {
    public render() {
        const {
            texts,
        } = this.props;

        if (!Array.isArray(texts)) {
            return null;
        }

        return this._renderParagraphGroups(texts);
    }

    private _renderParagraphGroups(texts: ITextState[]) {
        const {
            fragmentId,
            fragmentInspector: FragmentInspector,
        } = this.props;

        const groups = [];

        let paragraphNumber = 0;
        while (true) {
            const args: IRenderArgs = {
                fragmentSelected: false,
                paragraphNumber,
            };
            const paragraphs = this._renderParagraphs(texts, args);

            if (paragraphs.length === 0) {
                break;
            }

            groups.push(<ParagraphGroup selected={args.fragmentSelected} key={paragraphNumber}>
                {paragraphs}
            </ParagraphGroup>);

            if (args.fragmentSelected) {
                groups.push(<FragmentInspector key="inspect" fragmentId={fragmentId} />);
            }

            paragraphNumber += 1;
        }

        return groups;
    }

    private _renderParagraphs(texts: ITextState[], args: IRenderArgs) {
        return texts.reduce((paragraphs, text, key) => {
            const component = this._renderParagraph(text, key, args);

            if (component !== null) {
                paragraphs.push(component);
            }

            return paragraphs;
        }, []);
    }

    private _renderParagraph(text: ITextState, key: number, args: IRenderArgs) {
        const {
            paragraphNumber,
        } = args;

        const paragraph = text.paragraphs[paragraphNumber];

        if (paragraph === undefined) {
            return null;
        }

        return <Paragraph key={key} transformerName={text.transformerName} paragraphNumber={paragraphNumber}>
            {paragraph.map((fragment) => this._renderFragment(fragment, args))}
        </Paragraph>;
    }

    private _renderFragment(fragment: IFragmentInSentenceState, args: IRenderArgs) {
        const {
            fragmentId,
            onFragmentClick,
        } = this.props;

        const selected = fragment.id === fragmentId;
        if (selected) {
            args.fragmentSelected = selected;
        }

        return <Fragment
            key={fragmentId}
            fragment={fragment}
            onClick={onFragmentClick}
            selected={selected}
        />;
    }
}
