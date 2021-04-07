import React from 'react';

import { IProps } from './Sentence._types';

function Sentence(props: IProps) {
    const {
        sentence,
    } = props;
    return <blockquote>
        <a className="block-link" href={`/phrases/${sentence.languageId}-l/${sentence.id}-p`}>
            <h3>{sentence.name}</h3>
            <p>{sentence.description}</p>
        </a>
        <footer>
            {sentence.source} by <a href="http://localhost:8000/author/170-aldaleon">Aldaleon</a>.
        </footer>
    </blockquote>;
}

export default Sentence;
