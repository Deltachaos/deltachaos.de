module Jekyll
  class Spotify < Liquid::Tag

    def initialize(tag_name, id, tokens)
        super
        @markup       = id
    end

    def render(context)
      if not @markup.start_with?('spotify:')
        @markup = 'spotify:' + @markup
      end

      if @markup
        %(<iframe class="spotify" src=\"https://embed.spotify.com/?uri=#{@markup}\" frameborder=\"0\" allowtransparency=\"true\" allow=\"encrypted-media\"></iframe>)
      else
        %(Error input, expected syntax: {% spotify id [width] [height] %})
      end
    end
  end
end

Liquid::Template.register_tag('spotify', Jekyll::Spotify)