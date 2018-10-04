
import React from 'react';

const AdClient = 'ca-pub-8268364504414566';
const AdMinimumHeight = 20; // minimum ad block height in pixels

export class Ad extends React.Component {
    constructor(props, slot, format = 'fluid', layoutKey = '-gw-3+1f-3d+2z') {
        super(props);

        this._ad = null;
        this._adContainer = null;
        this._observer = null;

        this._slot = slot;
        this._format = format;
        this._layoutKey = layoutKey;
    }

    componentDidMount() {
        window.setTimeout(() => {
            try {
                (adsbygoogle = window.adsbygoogle || []).push({});
            } catch (e) {
                /* suppress */
            }
        }, 50 /* instantaneous not necessary */);

        this._observer = new MutationObserver(this.onResize.bind(this));
        this._observer.observe(this._ad, { childList: false, attributes: true });
    }

    componentWillUnmount() {
        this._observer.disconnect();
        this._observer = null;
    }

    onResize(changes) {
        const style = changes.find(c => c.attributeName === 'style');
        if (!style) {
            return;
        }

        const visible = this._ad.offsetHeight >= AdMinimumHeight;
        this._adContainer.style.visibility = visible ? '' : 'hidden';
    }

    render() {
        return <div className="ad" ref={c => this._adContainer = c}>
            <ins className="adsbygoogle"
                ref={c => this._ad = c}
                style={{ display: 'block' }}
                data-ad-format={this._format}
                data-ad-layout-key={this._layoutKey}
                data-ad-client={AdClient}
                data-ad-slot={this._slot} />
        </div>;
    }

    renderAd() {
        return null;
    }
}

export class GlossSectionAd extends Ad {
    static get name() {
        return 'gloss-section';
    }

    constructor(props) {
        super(props, '9363211011');
    }
}

export class DiscussAd extends Ad {
    static get name() {
        return 'discuss';
    }

    constructor(props) {
        super(props, '7017083422');
    }
}

export class HomeAd extends Ad {
    static get name() {
        return 'home';
    }

    constructor(props) {
        super(props, '6826878711');
    }
}

export class SentenceAd extends Ad {
    static get name() {
        return 'sentence';
    }

    constructor(props) {
        super(props, '3237620511');
    }
}
