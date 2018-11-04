module Music
  class Generator < Jekyll::Generator
    def generate(site)
      if site.data.key?('collection')
        collection_posts = Hash.new
        site.posts.docs.each do |item|
          if not item.data['draft']
            if item.data.key?('discogs')
              item.data['discogs'].to_s.split(',').each do |id|
                collection_posts[id.to_i] = item
              end
            end
          end
        end

        site.data['collection'].each do |key, item|
          if collection_posts.key?(item['master_id'])
            item['page'] = collection_posts[item['master_id']].url
          end
        end
      end
    end
  end
end