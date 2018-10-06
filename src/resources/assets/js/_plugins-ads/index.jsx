import EDConfig from 'ed-config';
import * as ads from './ads';
import React from 'react';
import ReactDOM from 'react-dom';

const load = () => {
    EDConfig.addPlugins('book-gloss-section', [ ads.GlossSectionAd ]);

    const adContainers = document.querySelectorAll('.ed-ad');
    // Avoid using `forEach` since it is not compatible with older browsers.
    for (var i = 0; i < adContainers.length; i += 1) {
        const container = adContainers.item(i);
        const adName = container.dataset.adName;

        const Ad = Object.values(ads).find(ad => ad.name === adName);
        if (Ad) {
            ReactDOM.render(<Ad />, container);
        }
    }
};

load();
