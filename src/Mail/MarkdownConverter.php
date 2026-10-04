<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Mail;

/**
 * Converts a small, email oriented subset of Markdown into HTML or plain text.
 *
 * Supported block syntax:
 *  - paragraphs separated by blank lines; a single line break is kept as line break
 *  - headings: "# Heading" up to "###### Heading"
 *  - unordered lists: "- item", "* item" or "+ item"
 *  - ordered lists: "1. item" or "1) item"
 *  - indented lines directly following a list item continue that item
 *  - horizontal rules: "---", "***" or "___"
 *
 * Supported inline syntax:
 *  - **bold** / __bold__, *italic* / _italic_, `code`
 *  - links: [text](https://example.org) and autolinks <https://example.org>
 *    (only http, https and mailto URLs become links)
 *  - backslash escapes for Markdown characters, see escape()
 */
class MarkdownConverter
{
    private const ESCAPABLE_CHARS = '\\`*_[](){}#+-.!<>|';
    private const PLACEHOLDER = "\x1A";

    /**
     * Escapes all Markdown characters in a text, so it is rendered literally. Use this for dynamic content like names.
     */
    public static function escape(string $text): string
    {
        return addcslashes($text, self::ESCAPABLE_CHARS);
    }

    public function toHtml(string $markdown): string
    {
        $html = [];
        foreach ($this->parseBlocks($markdown) AS $block) {
            switch ($block['type']) {
                case 'heading':
                    $level = $block['level'];
                    $html[] = "<h$level>" . $this->renderInline($block['text'], true) . "</h$level>";
                    break;
                case 'hr':
                    $html[] = '<hr>';
                    break;
                case 'ul':
                case 'ol':
                    $items = array_map(fn(array $lines) => '<li>' . $this->renderLines($lines, true) . '</li>', $block['items']);
                    $start = $block['type']=='ol' && $block['start']!=1 ? ' start="' . $block['start'] . '"' : '';
                    $html[] = '<' . $block['type'] . $start . '>' . implode('', $items) . '</' . $block['type'] . '>';
                    break;
                default:
                    $html[] = '<p>' . $this->renderLines($block['lines'], true) . '</p>';
            }
        }

        return "<!DOCTYPE html>\n<html><head><meta charset=\"UTF-8\"></head>"
            . "<body style=\"font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.5;\">\n"
            . implode("\n", $html)
            . "\n</body></html>";
    }

    public function toText(string $markdown): string
    {
        $text = [];
        foreach ($this->parseBlocks($markdown) AS $block) {
            switch ($block['type']) {
                case 'heading':
                    $heading = $this->renderInline($block['text'], false);
                    $text[] = $heading . "\n" . str_repeat($block['level']==1 ? '=' : '-', max(3, mb_strlen($heading)));
                    break;
                case 'hr':
                    $text[] = str_repeat('-', 20);
                    break;
                case 'ul':
                case 'ol':
                    $items = [];
                    foreach ($block['items'] AS $i => $lines) {
                        $bullet = $block['type']=='ol' ? ($block['start'] + $i) . '. ' : '- ';
                        $items[] = $bullet . str_replace("\n", "\n" . str_repeat(' ', strlen($bullet)), $this->renderLines($lines, false));
                    }
                    $text[] = implode("\n", $items);
                    break;
                default:
                    $text[] = $this->renderLines($block['lines'], false);
            }
        }
        return implode("\n\n", $text);
    }

    private function parseBlocks(string $markdown): array
    {
        $blocks = [];
        $current = null;
        $close = function () use (&$blocks, &$current) {
            if ($current!==null) $blocks[] = $current;
            $current = null;
        };

        foreach (preg_split('/\r\n|\r|\n/', $markdown) AS $line) {
            $line = rtrim($line);

            if ($line=='') {
                $close();
            } else if (preg_match('/^(#{1,6})\s+(.*?)(\s+#+)?$/', $line, $m)) {
                $close();
                $blocks[] = ['type' => 'heading', 'level' => strlen($m[1]), 'text' => $m[2]];
            } else if (preg_match('/^ {0,3}(-{3,}|\*{3,}|_{3,})$/', $line)) {
                $close();
                $blocks[] = ['type' => 'hr'];
            } else if (preg_match('/^ {0,3}[-*+]\s+(.*)$/', $line, $m)) {
                if ($current===null || $current['type']!='ul') {
                    $close();
                    $current = ['type' => 'ul', 'items' => []];
                }
                $current['items'][] = [$m[1]];
            } else if (preg_match('/^ {0,3}(\d{1,9})[.)]\s+(.*)$/', $line, $m)) {
                if ($current===null || $current['type']!='ol') {
                    $close();
                    $current = ['type' => 'ol', 'start' => intval($m[1]), 'items' => []];
                }
                $current['items'][] = [$m[2]];
            } else if ($current!==null && ($current['type']=='ul' || $current['type']=='ol') && preg_match('/^\s+(.*)$/', $line, $m)) {
                $current['items'][count($current['items'])-1][] = $m[1];
            } else {
                if ($current===null || $current['type']!='p') {
                    $close();
                    $current = ['type' => 'p', 'lines' => []];
                }
                $current['lines'][] = $line;
            }
        }
        $close();

        return $blocks;
    }

    private function renderLines(array $lines, bool $isHtml): string
    {
        return implode($isHtml ? "<br>\n" : "\n", array_map(fn(string $line) => $this->renderInline($line, $isHtml), $lines));
    }

    private function renderInline(string $text, bool $isHtml): string
    {
        // protected fragments (links, escapes, code) are replaced by placeholders, so they are not processed any further
        $fragments = [];
        $protect = function (string $fragment) use (&$fragments): string {
            $fragments[] = $fragment;
            return self::PLACEHOLDER . (count($fragments)-1) . self::PLACEHOLDER;
        };
        $plain = fn(string $value) => $isHtml ? htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $value;

        // links first, as their labels are rendered recursively (an escaped "\[" does not start a link)
        $text = preg_replace_callback('/(?<!\\\\)\[((?:\\\\.|[^\]\\\\])+)\]\(\s*<?([^\s)>]+)>?\s*\)/', fn($m) => $protect($this->renderLink($m[2], $m[1], $isHtml)), $text);
        $text = preg_replace_callback('/<((?:https?:\/\/|mailto:)[^\s>]+)>/i', fn($m) => $protect($this->renderLink($m[1], null, $isHtml)), $text);
        $text = preg_replace_callback('/\\\\([' . preg_quote(self::ESCAPABLE_CHARS, '/') . '])/', fn($m) => $protect($plain($m[1])), $text);
        $text = preg_replace_callback('/`([^`]+)`/', fn($m) => $protect($isHtml ? '<code>' . $plain($m[1]) . '</code>' : $m[1]), $text);

        $text = $plain($text);
        $text = $this->renderEmphasis($text, $isHtml);

        return preg_replace_callback('/' . self::PLACEHOLDER . '(\d+)' . self::PLACEHOLDER . '/', fn($m) => $fragments[intval($m[1])], $text);
    }

    private function renderEmphasis(string $text, bool $isHtml): string
    {
        $rules = [
            '/\*\*(?=\S)(.+?)(?<=\S)\*\*/u' => 'strong',
            '/(?<![\p{L}\p{N}_])__(?=\S)(.+?)(?<=\S)__(?![\p{L}\p{N}_])/u' => 'strong',
            '/\*(?=\S)(.+?)(?<=\S)\*/u' => 'em',
            '/(?<![\p{L}\p{N}_])_(?=\S)(.+?)(?<=\S)_(?![\p{L}\p{N}_])/u' => 'em',
        ];
        foreach ($rules AS $pattern => $tag) {
            $text = preg_replace($pattern, $isHtml ? "<$tag>$1</$tag>" : '$1', $text);
        }
        return $text;
    }

    private function renderLink(string $url, ?string $label, bool $isHtml): string
    {
        $labelText = $label===null ? $url : $this->renderInline($label, false);

        if (!preg_match('/^(https?:\/\/|mailto:)/i', $url)) {
            // unsupported scheme: render label only
            return $label===null ? ($isHtml ? htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $url) : $this->renderInline($label, $isHtml);
        }

        if (!$isHtml) {
            $displayedUrl = preg_replace('/^mailto:/i', '', $url);
            return $labelText==$url || $labelText==$displayedUrl ? $displayedUrl : "$labelText ($displayedUrl)";
        }

        $href = htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $labelHtml = $label===null ? htmlspecialchars(preg_replace('/^mailto:/i', '', $url), ENT_QUOTES | ENT_HTML5, 'UTF-8') : $this->renderInline($label, true);
        return "<a href=\"$href\">$labelHtml</a>";
    }
}
