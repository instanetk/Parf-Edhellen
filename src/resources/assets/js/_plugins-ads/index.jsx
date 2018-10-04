import React from 'react';
import EDConfig from 'ed-config';

class GlossSectionAd extends React.Component {
    componentWillMount() {
        try {
            (adsbygoogle = window.adsbygoogle || []).push({});
        } catch (e) {
            /* suppress */
        }
    }

    render() {
        return <div className="ad">
            <ins className="adsbygoogle"
                style={{ display: 'block' }}
                data-ad-format="fluid"
                data-ad-layout-key="-gw-3+1f-3d+2z"
                data-ad-test="on"
                data-ad-client="ca-pub-8268364504414566"
                data-ad-slot="9363211011" />
        </div>;
    }
}

const load = () => {
    EDConfig.addPlugins('book-gloss-section', [ GlossSectionAd ]);
};

load();
