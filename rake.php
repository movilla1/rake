<?php
namespace Rake;
/** 
 * This class is implemented based on the Rapid Automatic Keyword Extraction (RAKE) algorithm as described in:
 * Rose, S., Engel, D., Cramer, N., & Cowley, W. (2010). Automatic Keyword Extraction from Individual Documents.
 * In M. W. Berry & J. Kogan (Eds.), Text Mining: Theory and Applications: John Wiley & Sons. 
 * Based on the python implementation under MIT License
 **/
class Rake
{
  var $stop_words_path;
  var $_stop_words_pattern;
  var $_keywords;

  /**
   * The constructor must get a path to the stop word list .txt file
   *
   * @param string $file_path
   */
  public function __construct($file_path)
  {
    $this->stop_words_path = $file_path;
    $this->_stop_words_pattern = $this->build_stop_word_regex($this->stop_words_path);
  }

  /**
   * This function actually applies the algorithm to get the keywords in an array sorted and grouped 
   * by weight.
   *
   * @param string $text with the text to run the keyword extraction to
   * @return array with the keywords grouped by weight
   */
  public function run($text)
  {
    $sentence_list = $this->split_sentences($text);

    $phrase_list = $this->generate_candidate_keywords($sentence_list, $this->_stop_words_pattern);

    $word_scores = $this->calculate_word_scores($phrase_list);

    $keyword_candidates = $this->generate_candidate_keyword_scores($phrase_list, $word_scores);

    $sorted_keywords = $this->sort_keywords($keyword_candidates);
    $this->_keywords = $sorted_keywords;
    return $sorted_keywords;
  }

  /** 
   * This function returns the topmost two keywords or the one whose weight is over 20
   * 
   * @param array $keywords array (optional, if nothing is sent it will use the last run's result)
   * @return array with the topmost 2 keywords
   */
  public function getSuggestedKeywords($keywords=false) {
    $keywords_out = "";
    $keywords_to_join = [];
    $total_weight = 0;
    $use_keywords = ($keywords===false) ? $this->_keywords : $keywords;
    foreach ($use_keywords as $keyword) {
      if (count($keyword)>2) {
        $keywords_to_join[] = $keyword[0];
        $total_weight += $keyword['weight'];
        if ($total_weight<20) {
          $keywords_to_join[] = $keyword[1];
          $total_weight *= 2;
        }
      } else if ($total_weight < 20)  {
        $keywords_to_join[] = $keyword[0];
        $total_weight += $keyword['weight'];
      }
      if ($total_weight>20 || count($keywords_to_join)>1) {
        break;
      }
    }
    return $keywords_to_join;
  }

  /**
   * This function returns the keywords from the algorithm grouped by weight in most relevant 
   * to least relevant order
   *
   * @param array $keywords resulting from the rake algorithm
   * @return array with the grouped keywords
   */
  protected function sort_keywords($keywords) {
    $values = array_values($keywords);
    sort($values);
    $sorted = array_unique(array_reverse($values, true));
    $ret = [];
    $row=0;
    foreach ($sorted as $k) {
      $ret[$row] = array_keys($keywords, $k);
      $ret[$row]["weight"] = $k;
      $row++;
    }
    
    return $ret;
  }

  /**
   * Utility function to load stop words from a file and return as a list of words
   * @param stop_word_file Path and file name of a file containing stop words.
   * @return list A list of stop words.
   */
  protected function load_stop_words($stop_word_file)
  {
    $stop_words = [];
    $stop_contents = file_get_contents($stop_word_file);
    $lines = explode("\n", $stop_contents);
    foreach ($lines as $line) {
      if (substr(trim($line), 0, 1) != "#") {
        $words = explode(" ", $line);
        foreach ($words as $word) { # in case more than one per line
        $stop_words[] = $word;
        }
      }
    }
    return $stop_words;
  }

  /**
   * Utility function to return a list of all words that are have a length greater than a specified number of characters.
   * @param text The text that must be split in to words.
   * @param min_word_return_size The minimum no of characters a word must have to be included.
   */
  protected function separate_words($text, $min_word_return_size)
  {
    $splitter = '/[^a-zA-Z0-9_\+\-\/]/';
    $words = [];
    $matches = [];
    $matches = preg_split($splitter, $text);
    foreach ($matches as $single_word) {
      $current_word = strtolower(trim($single_word));
      #leave numbers in phrase, but don't count as words, since they tend to invalidate scores of their phrases
      if (strlen($current_word) > $min_word_return_size && $current_word != '' && !is_numeric($current_word)) {
        $words[] = $current_word;
      }
    }
    return $words;
  }
  /**
   * Utility function to return a list of sentences.
   * @param text The text that must be split in to sentences.
   */
  protected function split_sentences($text)
  {
    $sentence_delimiters = '/[\.\!\?\,\;\:\t"\(\)\']|\s\-\s/';
    $matches = [];
    $matches = preg_split($sentence_delimiters, $text);
    return array_map("trim", $matches);
  }

  public function build_stop_word_regex($stop_word_file_path)
  {
    $stop_word_list = $this->load_stop_words($stop_word_file_path);
    $stop_word_regex_list = [];

    foreach ($stop_word_list as $word) {
      if (strlen(trim($word))>0) {
        $word_regex = '\b' . $word . '(?![\w-])'; # added look ahead for hyphen
        $stop_word_regex_list[] = $word_regex;
      }
    }
    $stop_word_pattern = "/" . join("|", $stop_word_regex_list) . "/i";
    return $stop_word_pattern;
  }

  protected function generate_candidate_keywords($sentence_list, $stopword_pattern)
  {
    $phrase_list = [];
    foreach ($sentence_list as $s) {
      $tmp = preg_replace($stopword_pattern, "|", $s);
      $phrases = explode("|", $tmp);
      foreach ($phrases as $phrase) {
        $phrase = strtolower(trim($phrase));
        if ($phrase != "") {
          $phrase_list[] = $phrase;
        }
      }
    }
    return $phrase_list;
  }

  protected function calculate_word_scores($phraseList)
  {
    $word_frequency = [];
    $word_degree = [];
    foreach ($phraseList as $phrase) {
      $word_list = $this->separate_words($phrase, 0);
      $word_list_length = count($word_list);
      $word_list_degree = $word_list_length - 1;
      #if word_list_degree > 3: word_list_degree = 3 #exp.
      foreach ($word_list as $word) {
        $word_frequency[$word] = 0;
        $word_frequency[$word] += 1;
        $word_degree[$word] = 0;
        $word_degree[$word] += $word_list_degree; #orig.
        #word_degree[word] += 1/(word_list_length*1.0) #exp.
      }
    }
    foreach ($word_frequency as $item => $value) {
      $word_degree[$item] = $word_degree[$item] + $word_frequency[$item];
    }
    # Calculate Word scores = deg(w)/frew(w)
    $word_score = [];
    foreach ($word_frequency as $item => $value) {
      $word_score[$item] = 0;
      $word_score[$item] = $word_degree[$item] / ($word_frequency[$item] * 1.0); #orig.
    }
    #word_score[item] = word_frequency[item]/(word_degree[item] * 1.0) #exp.
    return $word_score;
  }

  protected function generate_candidate_keyword_scores($phrase_list, $word_score)
  {
    $keyword_candidates = [];
    foreach ($phrase_list as $phrase) {
      $keyword_candidates[$phrase] = 0;
      $word_list = $this->separate_words($phrase, 0);
      $candidate_score = 0;
      foreach ($word_list as $word) {
        $candidate_score += $word_score[$word];
      }
      $keyword_candidates[$phrase] = $candidate_score;
    }
    return $keyword_candidates;
  }

}
