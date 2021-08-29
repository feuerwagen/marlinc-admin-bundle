$(document).ready(function() {
    $('input').on('ifChanged', function (event) { $(event.target).trigger('change'); });

    $('*[data-source]').each(function() {
        var $target = $(this);
        var $targetWrapper = $target.parents('.form-group');
        var $source = $($target.data('source'));

        if ($(this).data('type') === 'checkbox') {
            if ($source.val() !== $target.data('value')) {
                $targetWrapper.hide();
            }

            $source.on('change', function() {
                $targetWrapper.slideToggle();
            });
        } else if ($(this).data('type') === 'text' || $(this).data('type') === 'select') {
            switch ($target.data('comparison')) {
                case '!=':
                    if ($source.val() === $target.data('value')) {
                        $targetWrapper.hide();
                        return;
                    }
                    break;
                default:
                    if ($source.val() !== $target.data('value')) {
                        $targetWrapper.hide();
                        return;
                    }
                    break;
            }

            $source.on('change', function() {
                switch ($target.data('comparison')) {
                    case '!=':
                        if ($(this).val() !== $target.data('value')) {
                            $targetWrapper.slideDown();
                            return;
                        }
                        break;
                    default:
                        if ($(this).val() === $target.data('value')) {
                            $targetWrapper.slideDown();
                            return;
                        }
                        break;
                }

                $targetWrapper.slideUp();
            });
        } else if ($(this).data('type') === 'number') {
            switch ($target.data('comparison')) {
                case '>':
                    if ($source.val() <= $target.data('value')) {
                        $targetWrapper.hide();
                    }
                    break;
                case '<':
                    if ($source.val() >= $target.data('value')) {
                        $targetWrapper.hide();
                    }
                    break;
                case '=':
                    if ($source.val() !== $target.data('value')) {
                        $targetWrapper.hide();
                        return;
                    }
                    break;
                default:
                    if ($source.val() === $target.data('value')) {
                        $targetWrapper.hide();
                        return;
                    }
                    break;
            }

            $source.on('change', function() {
                switch ($target.data('comparison')) {
                    case '>':
                        if ($(this).val() > $target.data('value')) {
                            $targetWrapper.slideDown();
                            return null;
                        }
                        break;
                    case '<':
                        if ($(this).val() < $target.data('value')) {
                            $targetWrapper.slideDown();
                            return;
                        }
                        break;
                    case '=':
                        if ($(this).val() === $target.data('value')) {
                            $targetWrapper.slideDown();
                            return;
                        }
                        break;
                    default:
                        if ($(this).val() !== $target.data('value')) {
                            $targetWrapper.slideDown();
                            return;
                        }
                        break;
                }

                $targetWrapper.slideUp();
            });
        }
    });
});