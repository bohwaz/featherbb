<?php
/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

use FeatherBB\Core\Interfaces\Cache;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\User;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Unparser;

class Parser
{
    private $parser;

    private $renderer;

    private $cacheDir;

    private $smilies;

    public function __construct()
    {
        $this->cacheDir = ForumEnv::get('FORUM_CACHE_DIR').'/parser';

        // Load smilies
        if (!Cache::isCached('smilies')) {
            Cache::store('smilies', \FeatherBB\Model\Cache::getSmilies());
        }
        $this->smilies = Cache::retrieve('smilies');

        if (Cache::isCached('s9eparser') && Cache::isCached('s9erenderer')) {
            $this->parser = unserialize(Cache::retrieve('s9eparser'));
            $this->renderer = unserialize(Cache::retrieve('s9erenderer'));
        } else {
            $this->configureParser();
        }
    }

    /**
     * TODO Build bundles depend forum config and user group rights
     */
    private function configureParser()
    {
        $renderer = $parser = null;
        $configurator = new Configurator;
        $configurator->plugins->load('Autoemail');//Fatdown & Forum default
        $configurator->plugins->load('Autolink');//Fatdown & Forum default
        $configurator->plugins->load('Escaper');//Fatdown default
        $configurator->plugins->load('Litedown');//Fatdown default
        $configurator->plugins->load('PipeTables');//Fatdown default

        // Load BBCodes
        $configurator->plugins->load('BBCodes');//Forum default
        $configurator->BBCodes->addFromRepository('B');
        $configurator->BBCodes->addFromRepository('U');
        $configurator->BBCodes->addFromRepository('I');
        $configurator->BBCodes->addFromRepository('S');
        $configurator->BBCodes->addFromRepository('DEL');
        $configurator->BBCodes->addFromRepository('INS');
        $configurator->BBCodes->addFromRepository('EM');
        $configurator->BBCodes->addFromRepository('COLOR');
        $configurator->BBCodes->addFromRepository('H1');
        $configurator->BBCodes->addFromRepository('URL');
        $configurator->BBCodes->addFromRepository('IMG');
        $configurator->BBCodes->addFromRepository('QUOTE');
        $configurator->BBCodes->addFromRepository('CODE');
        $configurator->BBCodes->addFromRepository('LIST');
        $configurator->BBCodes->addFromRepository('CENTER');
        $configurator->BBCodes->addFromRepository('RIGHT');
        $configurator->BBCodes->addFromRepository('LEFT');
        $configurator->BBCodes->addFromRepository('JUSTIFY');

        // Alias COLOUR to COLOR
        $configurator->BBCodes->add('COLOUR', ['defaultAttribute' => 'color'])->tagName = 'COLOR';

        // Add some default limits
        $configurator->tags['QUOTE']->nestingLimit = 3;
        $configurator->tags['LIST']->nestingLimit = 5;

        $configurator->registeredVars['cacheDir'] = ForumEnv::get('FORUM_CACHE_DIR');

        // Get an instance of the parser and the renderer
        extract($configurator->finalize());

        // We save the parser and the renderer to the disk for easy reuse
        Cache::store('s9eparser', serialize($parser));
        Cache::store('s9erenderer', serialize($renderer));

        $this->parser = $parser;
        $this->renderer = $renderer;
    }

    /**
     * Parse post or signature message text.
     *
     * @param string &$text
     * @param integer $hideSmilies
     * @return string
     */
    public function parseBbcode(&$text, $hideSmilies = 0)
    {
        $xml  = $this->parser->parse($text);
        $html = $this->renderer->render($xml);

        if (!$hideSmilies) {
            $html = $this->doSmilies($html);
        }

        return $html;
    }

    /**
     * Parse message text
     *
     * @param string $text
     * @param integer $hideSmilies
     * @return string
     */
    public function parseMessage($text, $hideSmilies)
    {
        if (ForumSettings::get('o_censoring') == 1) {
            $text = Utils::censor($text);
        }

        if (ForumSettings::get('p_message_bbcode') == 1) {
            if (ForumSettings::get('p_message_img_tag') !== 1 || User::getPref('show.img') !== 1) {
                $this->parser->disablePlugin('Autoimage');// enable after parsing?
                $this->parser->disablePlugin('Autovideo');
            }

            $xml  = $this->parser->parse($text);
            $html = $this->renderer->render($xml);
        }
        else {
            $html = Utils::escape($text);
        }

        if (User::getPref('show.smilies') == 1 && ForumSettings::get('o_smilies') == 1 && $hideSmilies == 0) {
            $html = $this->doSmilies($html);
        }

        return $html;
    }

    /**
     * Parse signature text
     *
     * @param string $text
     * @return string
     */
    public function parseSignature($text)
    {
        if (ForumSettings::get('o_censoring') == 1) {
            $text = Utils::censor($text);
        }

        if (ForumSettings::get('p_sig_bbcode') == 1) {
            if (ForumSettings::get('p_sig_img_tag') !== 1 || User::getPref('show.img.sig') !== 1) {
                $this->parser->disablePlugin('Autoimage');// enable after parsing?
                $this->parser->disablePlugin('Autovideo');
            }

            $xml  = $this->parser->parse($text);
            $html = $this->renderer->render($xml);
        }
        else {
            $html = Utils::escape($text);
        }

        if (User::getPref('show.smilies') == 1 && ForumSettings::get('o_smilies') == 1) {
            $html = $this->doSmilies($html);
        }

        return $html;
    }

    public function parseForSave($text, &$errors)
    {
        $xml  = $this->parser->parse($text);

        return Unparser::unparse($xml);
    }

    /**
     * Pre-process text containing BBCodes. Check for integrity,
     * well-formedness, nesting, etc. Flag errors by wrapping offending
     * tags in a special [err] tag.
     *
     * @param string $text
     * @param array &$errors
     * @param integer $isSignature
     * @return string
     */
    public function preparseBbcode($text, &$errors, $isSignature = false)
    {
        $xml  = $this->parser->parse($text);
        return Unparser::unparse($xml);
    }

    /**
     * Display smilies
     * Credits: FluxBB
     * @param $text string containing smilies to parse
     * @return string text "smilied" :-)
     */
    private function doSmilies($text)
    {
        $text = ' '.$text.' ';
        foreach ($this->smilies as $smileyText => $smileyImg)
        {
            if (strpos($text, $smileyText) !== false) {
                $text = Utils::ucpPregReplace('%(?<=[>\s])'.preg_quote($smileyText, '%').'(?=[^\p{L}\p{N}])%um', '<img src="'.Utils::escape(URL::base().'/style/img/smilies/'.$smileyImg).'" alt="'.substr($smileyImg, 0, strrpos($smileyImg, '.')).'" />', $text);
            }
        }
        return substr($text, 1, -1);
    }
}
