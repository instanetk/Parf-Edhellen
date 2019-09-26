import classNames from 'classnames';
import React from 'react';

import { RoleManager, SecurityRole } from '@root/security';
import SharedReference from '@root/utilities/SharedReference';

import { IProps } from './GlossTitle._types';

import GlossAbsoluteLink from './GlossAbsoluteLink';
import NeologismIndicator from './NeologismIndicator';
import NumberOfComments from './NumberOfComments';

const GlossTitle = (props: IProps) => {
    const {
        gloss,
        toolbar,
    } = props;

    const className = classNames({ rejected: gloss.isRejected });
    const isAuthenticated = SharedReference.getInstance(RoleManager).currentRole !== SecurityRole.Anonymous;

    return <h3 className="gloss-word">
        <NeologismIndicator gloss={gloss} />
        <span itemProp="headline" className={className}>
            {gloss.word}
        </span>
        {gloss.inflectedWord && <span className="gloss-word__inflection">
            {gloss.inflectedWord.word}
            <span className="gloss-word__inflection__name">
                {gloss.inflectedWord.speech}
            </span>
            {gloss.inflectedWord.inflections && gloss.inflectedWord.inflections.map(
                (inflection) => <span key={inflection.inflectionId} className="gloss-word__inflection__name">
                    {inflection.name}
                </span>)}
        </span>}
        {toolbar && <React.Fragment>
            <NumberOfComments gloss={gloss} />
            <GlossAbsoluteLink gloss={gloss} />
            {isAuthenticated && <React.Fragment>
                <a href={`/dashboard/contribution/create/gloss?entity_id=${gloss.id}`}>
                    <span className="glyphicon glyphicon-pencil"></span>
                </a>
                <a href={`/dashboard/contribution/create/gloss?entity_id=${gloss.id}`}>
                    <span className="glyphicon glyphicon-trash"></span>
                </a>
            </React.Fragment>}
        </React.Fragment>}
    </h3>;
};

export default GlossTitle;
