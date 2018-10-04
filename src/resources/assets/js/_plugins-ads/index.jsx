import EDConfig from 'ed-config';
import * as ads from './ads';
import React from 'react';
import ReactDOM from 'react-dom';

const load = () => {
    EDConfig.addPlugins('book-gloss-section', [ ads.GlossSectionAd ]);

    document.querySelectorAll('.ed-ad').forEach(c => {
        const adName = c.dataset.adName;
        const Ad = Object.values(ads).find(c => c.name === adName);
        if (Ad) {
            ReactDOM.render(<Ad />, c);
        }
    });
};

load();
