<?php
namespace Mongolid\Model;

/**
 * If a model implements this interface it means that it can change its own class
 * based on given input. For example, a model specialization can be created.
 *
 * See the docblock of the `polymorph` method for more details.
 */
interface PolymorphableModelInterface
{
    /**
     * This may be implemented in order to make a model polymorphic.
     * For example, you may have three models within the same collection:
     * `Content`, `ArticleContent` and `VideoContent`.
     * By implementing this method it is possible to inform if the
     * model should be an `ArticleContent` or a `VideoContent` based
     * on the input.
     *
     * @example
     *  public function polymorph(array $input): string;
     *  {
     *      $type = $input['type'] ?? '';
     *
     *      if ($type === 'video') {
     *          return VideoContent::class;
     *      } elseif ($type === 'article') {
     *          return ArticleContent::class;
     *      }
     *
     *      return Article::class;
     *  }
     *
     * In the example above, if you call $object = Content::fill($input) and
     * the input has a "type" key with the value "video", then the $object returned
     * will be a VideoContent instead of a Content.
     *
     * @see HasAttributesInterface::fill
     *
     * @param array $input data that will tell which class should be instanced
     *
     * @return string model class to be instanced
     */
    public function polymorph(array $input): string;
}
