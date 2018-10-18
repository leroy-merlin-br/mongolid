<?php

namespace Mongolid\Model;

/**
 * If a model implements the PolymorphableInterface it means that, whenever the
 * entity is being "recovered" from the database, it will call the polymorph
 * method and retrieve the object returned from it.
 *
 * See the docblock of the `polymorph` method for more details.
 *
 * @see Mongolid\DataMapper\EntityAssembler
 */
interface PolymorphableInterface
{
    /**
     * The polymorphic method is something that may be implemented in order to
     * make a model polymorphic. For example: You may have three models within
     * the same collection: `Content`, `ArticleContent` and `VideoContent`.
     * By implementing the polymorph method it is possible to retrieve an
     * `ArticleContent` or a `VideoContent` object object by simply querying
     * within the `Content` model using first, find, where or all.
     *
     * Example:
     *  public function polymorph()
     *  {
     *      if ($this->video != null) {
     *          $obj = new VideoContent;
     *          $obj->fill($this->attributes);
     *
     *          return $obj;
     *      } else {
     *          return $this;
     *      }
     *  }
     *
     * In the example above, if you call Content::first() and the content
     * returned have the key video set, then the object returned will be
     * a VideoContent instead of a Content.
     *
     * @return mixed
     */
    public function polymorph();
}
