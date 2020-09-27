=begin
イメージ
draw_table(ar) => 
ar=[125,36,9991,697,776081,77981,51,5781,7973,26]

   n   |              nの約数                 |個数|
-------+--------------------------------------+----+
    125|1,**5,**25,***125,***,***,****,****,**|   4|
     36|1,**2,***3,*****4,**6,**9,**12,**18,36|   9|
   9991|1, 97, 103,  9991,***,***,****,****,**|   4|
    697|1,*17,**41,***697,***,***,****,****,**|   4|
 776081|1,229,3389,776081,***,***,****,****,**|   4|
  77981|1, 29,2689, 77981                     |   4|
     51|1,  3,  17,    51                     |   4|
   7973|1,  7,  17,    67,119,469,1139,7973   |   8|
     26|1,  2,  13,    26                     |   4|
-------+--------------------------------------+----+

1 arの中の各数の約数を配列に入れる
2 arの中の各数で約数の個数が最も多いものを求める
3
=end
ar=[125,36,9991,697,776081,77981,51,5781,7973,26,88,256,512]

#約数を求める
#関数名：divisors
#入力　：n (1以上整数）
#出力　：[配列](約数全体)

def divisors(n)    #前と後ろから約数を数える．
    divs1=[1]      #divs1：小さい方から数えた約数．
    if n > 1    
        divs2=[n]  #divs2：大きい方から数えた約数
    else
        divs2=[]
    end
    
    bottom = 2   #小さい方の最前線
    top = n / 2  #大きい方の最前線
    
    while bottom < top
        if n % bottom == 0
            divs1 << bottom
            divs2.unshift(top)
        end
        bottom += 1; top = n / bottom
    end
    
    if bottom == top && n%bottom == 0
        divs1 << bottom
    end
    return divs1 + divs2	
end

#arの中のそれぞれの要素について，それの約数が入った集合を合わせたもの．
#関数名：all_divs
#入力  : ar(配列)
#出力 : [[n_1約数],[n_2約数],...]
#関数内で，関数divisors(n)を用いる.

def all_divs(ar)
        divs_set = []
    for x in ar
                divs_set << divisors(x)
    end
        return divs_set
end

#all_divsで求めた約数の配列の中で，その要素数が最も多いものを出す．
#関数名：find_largest_divnum
#入力 : ar　配列
#出力 : 自然数
#関数内で，関数all_divs(ar)を用いる．

def find_largest_divnum(ar)
	return all_divs(ar).map{ |ar| ar.size}.max
end


#列で見た時に，約数の桁が最も大きいものを求める．
#関数名:find_each_column_digit
#入力 ar
#出力 [（1列目最大桁数＝1）,（2列目最大桁数），…]
#関数内で，all_divsとfind_largest_divnumを求める．

def find_each_column_digit(ar)
    column_maxs = []
    for i in 0..find_largest_divnum(ar)-1
        column_temp = []
        for x in 0..ar.size-1
			column_temp << all_divs(ar)[x][i].to_i.to_s.size
        end
        #ここまでで，列全体の桁数が上から順に入っているので，最大だけ取り出す.
		column_maxs << column_temp.max
    end
    return column_maxs
end

#左端のnを表示するための欄を求める.
#出力は自然数．
def find_n_column_width(ar)
    return (ar.max).to_s.size + 1
end

#nの約数を表示する欄の全幅を求める．
#関数名 find_total_divs_width
#出力は自然数．

def find_total_divs_width(ar)
    exp_width = 0
    for x in find_each_column_digit(ar)
        exp_width += x
	end
    return exp_width + find_largest_divnum(ar)
end


#約数の個数を表示する欄の用意する桁数を求める．
#関数名 find_right_edge_space
def find_right_edge_space(ar)
    return find_largest_divnum(ar).to_s.size + 3
end
#1行目及び2行目描画
#関数名 draw_top

def draw_top(ar)
    
    n_total_width = find_n_column_width(ar)  #n全体幅
    n_lr_width = n_total_width / 2           #ｎの左右に来る幅(lr:left&right)
    divs_total_width = find_total_divs_width(ar) - 1
    divs_lr_width = (divs_total_width-7) / 2 
    top_str = " "*n_lr_width + "n" + " "*n_lr_width + "|"
    top_str += " "*divs_lr_width + "nの約数" + " "*divs_lr_width + "|" + " "*(find_right_edge_space(ar)-4) + "個数|\n"
    top_str += "-"*n_total_width + "+" + "-"*divs_total_width + "+" + "-"*find_right_edge_space(ar) + "+\n"
    return top_str
end

def draw_body(ar)
    draw_body_str = "" 
	n_width = find_n_column_width(ar)                       #nの幅
    column_space = find_each_column_digit(ar)               #各列の最大幅が配列で入っている．
    all_divs = all_divs(ar); all_divs_size = all_divs.size  #全てのarの数値の約数の配列が入った配列.column_space[0]は必ず1である．
	max_divnum = find_largest_divnum(ar)                    #約数の個数の最大値
    right_edge_space = find_right_edge_space(ar)            #右端に個数を表示するときに使用．
    longest_length = n_width + find_total_divs_width(ar)    #約数の個数が最も多いものは端までにどれだけの長さがあるかを求める．
    
    all_divs.each do |ar|
        draw_body_str_temp = ""
		draw_body_str_temp += " "*(n_width - ar.max.to_s.size) + ar.max.to_s + "|"
        divs_str = ""
        for k in 0..ar.size-2
			divs_temp_size = ar[k].to_i.to_s.size
            divs_str += " "*(column_space[k]-divs_temp_size) + ar[k].to_s + ","
        end
		#最後は,が付かず，特別扱い
        l = ar.size-1
        divs_str += " "*(column_space[l]-ar[l].to_i.to_s.size) + ar[l].to_s
        
        #arの要素の個数が最大でないとき，その後ろの差分だけスペースが必要
		#現在長は最大長でない=>（最大長－現在の長さ）個のスペースを挿入．
        length_temp = (draw_body_str_temp + divs_str).size
        if length_temp != longest_length
            divs_str += " "*(longest_length-length_temp) 
        end
        draw_body_str += draw_body_str_temp + divs_str + "|" + " "*(right_edge_space  - ar.size.to_s.size) + ar.size.to_s + "|\n" 
    end
	return draw_body_str
end


#下の部分を書く．
def draw_bottom(ar)
    
    bottom_str = "-"*find_n_column_width(ar) + "+" + "-"*(find_total_divs_width(ar) - 1) + "+" + "-"*find_right_edge_space(ar) + "+\n"
    return bottom_str
end

def draw_table(ar)
    return draw_top(ar) + draw_body(ar) + draw_bottom(ar)
end
print draw_table(ar)