import React from 'react';
import clsx from 'clsx';
import Link from '@docusaurus/Link';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import Layout from '@theme/Layout';
import HomepageFeatures from '@site/src/components/HomepageFeatures';
import Tags from './_tags.md';

import styles from './index.module.css';

function HomepageHeader() {
    const {siteConfig} = useDocusaurusContext();
    return (
        <header className={clsx('hero hero--primary', styles.heroBanner)}>
            <div className="container">
                <h1 className="hero__title">{siteConfig.title}</h1>
                <p className="hero__subtitle">{siteConfig.tagline}</p>
                <p>
                    <b>Easy</b>, <b>powerful</b> and <b>ultrafast</b> ODM for PHP 7.1+ build on top of the&nbsp;
                    <Link to="https://docs.mongodb.org/ecosystem/drivers/php/" className="badge badge--secondary">
                        <strong>mongodb driver</strong>
                    </Link>.
                </p>
                <div className={styles.buttons}>
                    <Link
                        className="button button--secondary button--lg"
                        to={`/docs/${siteConfig.customFields.currentVersion}/quick-start`}>
                        Get started
                    </Link>
                </div>

                <div style={{marginTop: "60px", marginBottom: "-60px"}}>
                    <Tags />
                </div>
            </div>
        </header>
    );
}

export default function Home() {
    return (
        <Layout
            title="Easy, powerful and ultrafast ODM"
            description="Mongolid provides a beautiful, simple implementation for working with MongoDB.">
            <HomepageHeader />
            <main>
                <HomepageFeatures />
            </main>
        </Layout>
    );
}
